<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\TestCase\Service;

use DeutschePost\Sdk\Internetmarke\Exception\AuthenticationException;
use DeutschePost\Sdk\Internetmarke\Exception\DetailedServiceException;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceException;
use DeutschePost\Sdk\Internetmarke\Service\ServiceFactory;
use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

/**
 * Integration tests for error handling through the full plugin chain.
 *
 * Exercises ErrorPlugin → ServiceExceptionFactory conversion for:
 * - 401 on auth → AuthenticationException
 * - 400 on service call → DetailedServiceException
 * - 500 → ServiceException
 * - Transport errors → ServiceException
 * - 403 → AuthenticationException
 */
class ErrorHandlingTest extends TestCase
{
    private function loadFixture(string $path): string
    {
        return file_get_contents(__DIR__ . '/../../Provider/_files/' . $path);
    }

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
    public function unauthorizedOnAuthThrowsAuthenticationException(): void
    {
        $factory = new Psr17Factory();
        $mockClient = new MockClient();

        // Auth returns 401 — no service response needed
        $mockClient->addResponse(
            $factory->createResponse(401)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream($this->loadFixture('error/unauthorized401.json'))),
        );

        $service = $this->createFactory($mockClient)->createUserProfileService();

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionCode(401);

        $service->getProfile();
    }

    #[Test]
    public function forbiddenThrowsAuthenticationException(): void
    {
        $factory = new Psr17Factory();
        $mockClient = new MockClient();

        // Auth succeeds, service returns 403
        $mockClient->addResponse(
            $factory->createResponse(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream($this->loadFixture('authentication/success.json'))),
        );
        $mockClient->addResponse(
            $factory->createResponse(403)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream($this->loadFixture('error/forbidden403.json'))),
        );

        $service = $this->createFactory($mockClient)->createUserProfileService();

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionCode(403);

        $service->getProfile();
    }

    #[Test]
    public function badRequestThrowsDetailedServiceException(): void
    {
        $factory = new Psr17Factory();
        $mockClient = new MockClient();

        // Auth succeeds, service returns 400
        $mockClient->addResponse(
            $factory->createResponse(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream($this->loadFixture('authentication/success.json'))),
        );
        $mockClient->addResponse(
            $factory->createResponse(400)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream($this->loadFixture('error/badRequest400.json'))),
        );

        $service = $this->createFactory($mockClient)->createWalletService();

        $this->expectException(DetailedServiceException::class);
        $this->expectExceptionCode(400);

        $service->chargeWallet(1000);
    }

    #[Test]
    public function badRequestExceptionContainsParsedMessage(): void
    {
        $factory = new Psr17Factory();
        $mockClient = new MockClient();

        $mockClient->addResponse(
            $factory->createResponse(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream($this->loadFixture('authentication/success.json'))),
        );
        $mockClient->addResponse(
            $factory->createResponse(400)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream($this->loadFixture('error/badRequest400.json'))),
        );

        $service = $this->createFactory($mockClient)->createWalletService();

        try {
            $service->chargeWallet(1000);
            self::fail('Expected DetailedServiceException');
        } catch (DetailedServiceException $e) {
            // ErrorPlugin extracts title + detail from JSON body
            self::assertStringContainsString('Bad Request', $e->getMessage());
            self::assertStringContainsString('Authorization', $e->getMessage());
        }
    }

    #[Test]
    public function serverErrorThrowsDetailedServiceException(): void
    {
        $factory = new Psr17Factory();
        $mockClient = new MockClient();

        $mockClient->addResponse(
            $factory->createResponse(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream($this->loadFixture('authentication/success.json'))),
        );
        $mockClient->addResponse(
            $factory->createResponse(500)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream($this->loadFixture('error/serverError500.json'))),
        );

        $service = $this->createFactory($mockClient)->createCatalogService();

        $this->expectException(DetailedServiceException::class);
        $this->expectExceptionCode(500);

        $service->getPageFormats();
    }

    #[Test]
    public function transportErrorThrowsServiceException(): void
    {
        $mockClient = new MockClient();
        $mockClient->addException(new \RuntimeException('Connection refused'));

        $service = $this->createFactory($mockClient)->createApiInfoService();

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Connection refused');

        $service->getInfo();
    }

    #[Test]
    public function transportErrorOnAuthThrowsServiceException(): void
    {
        $mockClient = new MockClient();
        $mockClient->addException(new \RuntimeException('DNS resolution failed'));

        $service = $this->createFactory($mockClient)->createUserProfileService();

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('DNS resolution failed');

        $service->getProfile();
    }

    #[Test]
    public function errorResponsesAreLogged(): void
    {
        $factory = new Psr17Factory();
        $logger = new TestLogger();
        $mockClient = new MockClient();

        $mockClient->addResponse(
            $factory->createResponse(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream($this->loadFixture('authentication/success.json'))),
        );
        $mockClient->addResponse(
            $factory->createResponse(400)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream($this->loadFixture('error/badRequest400.json'))),
        );

        $serviceFactory = new ServiceFactory(
            'test-client-id',
            'test-client-secret',
            'test-user',
            'test-pass',
            $logger,
            $mockClient,
        );

        try {
            $serviceFactory->createWalletService()->chargeWallet(1000);
        } catch (DetailedServiceException) {
            // expected
        }

        // The LoggerPlugin logs all communication
        self::assertTrue(
            $logger->hasInfoThatContains('Bad Request')
            || $logger->hasErrorThatContains('Bad Request'),
            'Error response should be logged',
        );
    }
}
