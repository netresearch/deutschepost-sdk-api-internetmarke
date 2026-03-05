<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Service;

use DeutschePost\Sdk\Internetmarke\Api\Data\ApiInfoInterface;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceException;
use DeutschePost\Sdk\Internetmarke\Serializer\JsonSerializer;
use DeutschePost\Sdk\Internetmarke\Service\ApiInfoService;
use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ApiInfoServiceTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    #[Test]
    public function getInfoReturnsApiInfo(): void
    {
        $responseBody = json_encode([
            'amp' => [
                'name' => 'pp-post-internetmarke',
                'version' => 'v1.1.4',
                'rev' => '13',
                'env' => 'prod-eu',
                'description' => 'Internetmarke REST API',
            ],
        ], JSON_THROW_ON_ERROR);

        $mockClient = new MockClient();
        $mockClient->addResponse(
            $this->factory->createResponse(200)->withBody($this->factory->createStream($responseBody))
        );

        $service = new ApiInfoService(
            $mockClient,
            $this->factory,
            new JsonSerializer(),
            'https://api-eu.dhl.com/post/de/shipping/im/v1'
        );

        $info = $service->getInfo();

        self::assertInstanceOf(ApiInfoInterface::class, $info);
        self::assertSame('pp-post-internetmarke', $info->getName());
        self::assertSame('v1.1.4', $info->getVersion());
        self::assertSame('13', $info->getRev());
        self::assertSame('prod-eu', $info->getEnv());
        self::assertSame('Internetmarke REST API', $info->getDescription());
    }

    #[Test]
    public function isHealthyReturnsTrueOnSuccessfulResponse(): void
    {
        $responseBody = json_encode([
            'amp' => [
                'name' => 'pp-post-internetmarke',
                'version' => 'v1.1.4',
                'rev' => '13',
                'env' => 'prod-eu',
            ],
        ], JSON_THROW_ON_ERROR);

        $mockClient = new MockClient();
        $mockClient->addResponse(
            $this->factory->createResponse(200)->withBody($this->factory->createStream($responseBody))
        );

        $service = new ApiInfoService(
            $mockClient,
            $this->factory,
            new JsonSerializer(),
            'https://api-eu.dhl.com/post/de/shipping/im/v1'
        );

        self::assertTrue($service->isHealthy());
    }

    #[Test]
    public function isHealthyReturnsFalseOnError(): void
    {
        $mockClient = new MockClient();
        $mockClient->addException(new \RuntimeException('Connection refused'));

        $service = new ApiInfoService(
            $mockClient,
            $this->factory,
            new JsonSerializer(),
            'https://api-eu.dhl.com/post/de/shipping/im/v1'
        );

        self::assertFalse($service->isHealthy());
    }

    #[Test]
    public function getInfoThrowsServiceExceptionOnApiError(): void
    {
        $mockClient = new MockClient();
        $mockClient->addException(new \RuntimeException('timeout'));

        $service = new ApiInfoService(
            $mockClient,
            $this->factory,
            new JsonSerializer(),
            'https://api-eu.dhl.com/post/de/shipping/im/v1'
        );

        $this->expectException(ServiceException::class);

        $service->getInfo();
    }
}
