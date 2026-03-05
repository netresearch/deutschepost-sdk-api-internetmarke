<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\TestCase\Service;

use DeutschePost\Sdk\Internetmarke\Api\Data\WalletChargeInterface;
use DeutschePost\Sdk\Internetmarke\Service\ServiceFactory;
use DeutschePost\Sdk\Internetmarke\Test\Provider\Http\Service\WalletTestProvider;
use Http\Mock\Client as MockClient;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

/**
 * Integration test for WalletService through ServiceFactory.
 *
 * Authenticated service — auth response must be queued first.
 */
class WalletServiceTest extends TestCase
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
    public function chargeWalletReturnsParsedResult(): void
    {
        $mockClient = new MockClient();
        foreach (WalletTestProvider::chargeWalletSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createWalletService();
        $charge = $service->chargeWallet(2000);

        self::assertInstanceOf(WalletChargeInterface::class, $charge);
        self::assertSame('WALLET-ORD-123', $charge->getShopOrderId());
        self::assertSame(5000, $charge->getWalletBalance());
    }

    #[Test]
    public function sendsPutRequestWithAmountParam(): void
    {
        $mockClient = new MockClient();
        foreach (WalletTestProvider::chargeWalletSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createWalletService();
        $service->chargeWallet(1500);

        $requests = $mockClient->getRequests();
        self::assertCount(2, $requests);

        // Second request is the service call
        $walletRequest = $requests[1];
        self::assertSame('PUT', $walletRequest->getMethod());
        self::assertStringContainsString('/app/wallet', (string) $walletRequest->getUri());
        self::assertStringContainsString('amount=1500', (string) $walletRequest->getUri());
    }

    #[Test]
    public function tokenIsCachedAcrossMultipleServiceCalls(): void
    {
        $mockClient = new MockClient();

        // Queue: auth + first wallet + second wallet (no second auth needed)
        foreach (WalletTestProvider::chargeWalletSuccess() as $response) {
            $mockClient->addResponse($response);
        }
        // Add second wallet response for the second call
        $secondResponses = WalletTestProvider::chargeWalletSuccess();
        $mockClient->addResponse($secondResponses[1]); // Just the wallet response

        $factory = $this->createFactory($mockClient);
        $service = $factory->createWalletService();

        $service->chargeWallet(1000);
        $service->chargeWallet(2000);

        $requests = $mockClient->getRequests();
        // Auth + wallet + wallet = 3 requests (not 4, because token is cached)
        self::assertCount(3, $requests);
        self::assertSame('POST', $requests[0]->getMethod()); // auth
        self::assertSame('PUT', $requests[1]->getMethod());  // first wallet
        self::assertSame('PUT', $requests[2]->getMethod());  // second wallet
    }

    #[Test]
    public function contractValidation(): void
    {
        $mockClient = new MockClient();
        foreach (WalletTestProvider::chargeWalletSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createWalletService();
        $service->chargeWallet(1000);

        $requests = $mockClient->getRequests();
        self::assertRequestMatchesSpec($requests[0]); // auth
        self::assertRequestMatchesSpec($requests[1]); // wallet
    }
}
