<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\TestCase\Service;

use DeutschePost\Sdk\Internetmarke\Api\Data\ApiInfoInterface;
use DeutschePost\Sdk\Internetmarke\Service\ServiceFactory;
use DeutschePost\Sdk\Internetmarke\Test\Provider\Http\Service\ApiInfoTestProvider;
use Http\Mock\Client as MockClient;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

/**
 * Integration test for ApiInfoService through ServiceFactory.
 *
 * ApiInfoService is unauthenticated — no auth pre-loading needed.
 */
class ApiInfoServiceTest extends TestCase
{
    use ValidatesAgainstOpenApiSpec;

    private function createFactory(MockClient $client): ServiceFactory
    {
        return new ServiceFactory(
            'test-client-id',
            'test-client-secret',
            'test-user',
            'test-pass',
            new TestLogger(),
            $client,
        );
    }

    #[Test]
    public function getInfoReturnsParsedApiInfo(): void
    {
        $mockClient = new MockClient();
        foreach (ApiInfoTestProvider::getInfoSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createApiInfoService();
        $info = $service->getInfo();

        self::assertInstanceOf(ApiInfoInterface::class, $info);
        self::assertSame('pp-post-internetmarke', $info->getName());
        self::assertSame('v1.1.4', $info->getVersion());
        self::assertSame('13', $info->getRev());
        self::assertSame('prod-eu', $info->getEnv());
        self::assertSame('Internetmarke REST API', $info->getDescription());

        // Contract validation: request only.
        // Response validation skipped — spec has broken version pattern
        // (character class instead of optional group: [.\d{1,5}] vs (\.\d{1,5})?)
        $requests = $mockClient->getRequests();
        self::assertCount(1, $requests);
        self::assertRequestMatchesSpec($requests[0]);
    }

    #[Test]
    public function isHealthyReturnsTrueOnSuccess(): void
    {
        $mockClient = new MockClient();
        foreach (ApiInfoTestProvider::getInfoSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createApiInfoService();

        self::assertTrue($service->isHealthy());
    }

    #[Test]
    public function isHealthyReturnsFalseOnTransportError(): void
    {
        $mockClient = new MockClient();
        $mockClient->addException(new \RuntimeException('Connection refused'));

        $service = $this->createFactory($mockClient)->createApiInfoService();

        self::assertFalse($service->isHealthy());
    }

    #[Test]
    public function requestIncludesExpectedHeaders(): void
    {
        $mockClient = new MockClient();
        foreach (ApiInfoTestProvider::getInfoSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createApiInfoService();
        $service->getInfo();

        $request = $mockClient->getLastRequest();
        self::assertSame('GET', $request->getMethod());
        self::assertStringContainsString('application/json', $request->getHeaderLine('Accept'));
        self::assertStringContainsString('deutschepost-sdk-api-internetmarke', $request->getHeaderLine('User-Agent'));
    }

    #[Test]
    public function communicationIsLogged(): void
    {
        $logger = new TestLogger();
        $mockClient = new MockClient();
        foreach (ApiInfoTestProvider::getInfoSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $factory = new ServiceFactory(
            'test-client-id',
            'test-client-secret',
            'test-user',
            'test-pass',
            $logger,
            $mockClient,
        );

        $factory->createApiInfoService()->getInfo();

        self::assertTrue($logger->hasInfoThatContains('pp-post-internetmarke'));
    }
}
