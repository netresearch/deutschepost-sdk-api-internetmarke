<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Service;

use DeutschePost\Sdk\Internetmarke\Api\ApiInfoServiceInterface;
use DeutschePost\Sdk\Internetmarke\Api\CatalogServiceInterface;
use DeutschePost\Sdk\Internetmarke\Api\OrderServiceInterface;
use DeutschePost\Sdk\Internetmarke\Api\RefundServiceInterface;
use DeutschePost\Sdk\Internetmarke\Api\UserProfileServiceInterface;
use DeutschePost\Sdk\Internetmarke\Api\WalletServiceInterface;
use DeutschePost\Sdk\Internetmarke\Service\ServiceFactory;
use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ServiceFactoryTest extends TestCase
{
    private ServiceFactory $factory;

    protected function setUp(): void
    {
        $psr17 = new Psr17Factory();

        $this->factory = new ServiceFactory(
            'test-client-id',
            'test-client-secret',
            'test-user',
            'test-pass',
            new NullLogger(),
            new MockClient(),
            $psr17,
            $psr17,
        );
    }

    #[Test]
    public function constructsWithoutLogger(): void
    {
        $psr17 = new Psr17Factory();

        $factory = new ServiceFactory(
            'test-client-id',
            'test-client-secret',
            'test-user',
            'test-pass',
            client: new MockClient(),
            requestFactory: $psr17,
            streamFactory: $psr17,
        );

        $service = $factory->createApiInfoService();

        self::assertInstanceOf(ApiInfoServiceInterface::class, $service);
    }

    #[Test]
    public function createsApiInfoService(): void
    {
        $service = $this->factory->createApiInfoService();

        self::assertInstanceOf(ApiInfoServiceInterface::class, $service);
    }

    #[Test]
    public function createsCatalogService(): void
    {
        $service = $this->factory->createCatalogService();

        self::assertInstanceOf(CatalogServiceInterface::class, $service);
    }

    #[Test]
    public function createsOrderService(): void
    {
        $service = $this->factory->createOrderService();

        self::assertInstanceOf(OrderServiceInterface::class, $service);
    }

    #[Test]
    public function createsRefundService(): void
    {
        $service = $this->factory->createRefundService();

        self::assertInstanceOf(RefundServiceInterface::class, $service);
    }

    #[Test]
    public function createsUserProfileService(): void
    {
        $service = $this->factory->createUserProfileService();

        self::assertInstanceOf(UserProfileServiceInterface::class, $service);
    }

    #[Test]
    public function createsWalletService(): void
    {
        $service = $this->factory->createWalletService();

        self::assertInstanceOf(WalletServiceInterface::class, $service);
    }
}
