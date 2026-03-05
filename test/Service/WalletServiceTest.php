<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Service;

use DeutschePost\Sdk\Internetmarke\Api\Data\WalletChargeInterface;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceException;
use DeutschePost\Sdk\Internetmarke\Serializer\JsonSerializer;
use DeutschePost\Sdk\Internetmarke\Service\WalletService;
use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class WalletServiceTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    private function createService(MockClient $client): WalletService
    {
        return new WalletService(
            $client,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );
    }

    #[Test]
    public function chargeWalletReturnsWalletCharge(): void
    {
        $responseBody = json_encode([
            'shopOrderId' => 'WALLET-ORD-123',
            'walletBalance' => 5000,
        ], JSON_THROW_ON_ERROR);

        $mockClient = new MockClient();
        $mockClient->addResponse(
            $this->factory->createResponse(200)->withBody($this->factory->createStream($responseBody))
        );

        $service = $this->createService($mockClient);

        $charge = $service->chargeWallet(2000);

        self::assertInstanceOf(WalletChargeInterface::class, $charge);
        self::assertSame('WALLET-ORD-123', $charge->getShopOrderId());
        self::assertSame(5000, $charge->getWalletBalance());
    }

    #[Test]
    public function chargeWalletSendsPutWithAmountParam(): void
    {
        $responseBody = json_encode([
            'shopOrderId' => 'W-1',
            'walletBalance' => 100,
        ], JSON_THROW_ON_ERROR);

        $mockClient = new MockClient();
        $mockClient->addResponse(
            $this->factory->createResponse(200)->withBody($this->factory->createStream($responseBody))
        );

        $service = $this->createService($mockClient);

        $service->chargeWallet(1500);

        $lastRequest = $mockClient->getLastRequest();
        self::assertSame('PUT', $lastRequest->getMethod());

        $uri = (string) $lastRequest->getUri();
        self::assertStringContainsString('/app/wallet', $uri);
        self::assertStringContainsString('amount=1500', $uri);
    }

    #[Test]
    public function chargeWalletThrowsServiceExceptionOnError(): void
    {
        $mockClient = new MockClient();
        $mockClient->addException(new \RuntimeException('connection refused'));

        $service = $this->createService($mockClient);

        $this->expectException(ServiceException::class);

        $service->chargeWallet(1000);
    }
}
