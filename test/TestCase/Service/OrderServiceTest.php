<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\TestCase\Service;

use DeutschePost\Sdk\Internetmarke\Api\Data\OrderInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\VoucherInterface;
use DeutschePost\Sdk\Internetmarke\Api\Dpi;
use DeutschePost\Sdk\Internetmarke\Api\VoucherLayout;
use DeutschePost\Sdk\Internetmarke\Model\OrderRequest;
use DeutschePost\Sdk\Internetmarke\Model\PdfPreviewRequest;
use DeutschePost\Sdk\Internetmarke\Model\PngOrderRequest;
use DeutschePost\Sdk\Internetmarke\Model\PngPreviewRequest;
use DeutschePost\Sdk\Internetmarke\Model\RequestType\Address;
use DeutschePost\Sdk\Internetmarke\Model\RequestType\AddressBinding;
use DeutschePost\Sdk\Internetmarke\Model\ShoppingCartPosition;
use DeutschePost\Sdk\Internetmarke\Service\ServiceFactory;
use DeutschePost\Sdk\Internetmarke\Test\Provider\Http\Service\OrderTestProvider;
use Http\Mock\Client as MockClient;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

/**
 * Integration test for OrderService through ServiceFactory.
 *
 * All clients (unauthenticated, authenticated, download) share the same
 * transport MockClient. Queue order for a full PDF checkout:
 * 1. Auth token response
 * 2. Checkout API response (JSON)
 * 3. Label download response (binary)
 * 4. Manifest download response (binary, if manifestLink present)
 */
class OrderServiceTest extends TestCase
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

    private function createPosition(): ShoppingCartPosition
    {
        return new ShoppingCartPosition(
            10001,
            'ADDRESS_ZONE',
            new AddressBinding(
                new Address('Max Mustermann', 'Sträßchensweg 10', '53113', 'Bonn', 'DEU'),
                new Address('Erika Mustermann', 'Heidestraße 17', '51147', 'Köln', 'DEU'),
            ),
        );
    }

    #[Test]
    public function initializeCartReturnsShopOrderId(): void
    {
        $mockClient = new MockClient();
        foreach (OrderTestProvider::initCartSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createOrderService();
        $shopOrderId = $service->initializeCart();

        self::assertSame('98276337', $shopOrderId);

        // Verify request structure
        $requests = $mockClient->getRequests();
        self::assertCount(2, $requests); // auth + initCart
        self::assertSame('POST', $requests[1]->getMethod());
        self::assertStringContainsString('/app/shoppingcart', (string) $requests[1]->getUri());
    }

    #[Test]
    public function createOrderReturnsPdfOrderWithVouchers(): void
    {
        $mockClient = new MockClient();
        foreach (OrderTestProvider::checkoutPdfSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createOrderService();
        $request = new OrderRequest([$this->createPosition()], 85, 1);
        $order = $service->createOrder($request);

        self::assertInstanceOf(OrderInterface::class, $order);
        self::assertSame('98276337', $order->getShopOrderId());
        self::assertSame(42355, $order->getWalletBalance());
        self::assertStringContainsString('%PDF', $order->getLabel());
        self::assertStringContainsString('%PDF', $order->getManifest());

        $vouchers = $order->getVouchers();
        self::assertCount(2, $vouchers);
        self::assertContainsOnlyInstancesOf(VoucherInterface::class, $vouchers);
        self::assertSame('A00123C0390000000138', $vouchers[0]->getVoucherId());
        self::assertSame('00340434161094042557', $vouchers[0]->getTrackId());
        // Empty trackId normalized to null
        self::assertNull($vouchers[1]->getTrackId());
    }

    #[Test]
    public function createOrderSendsCorrectRequests(): void
    {
        $mockClient = new MockClient();
        foreach (OrderTestProvider::checkoutPdfSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createOrderService();
        $request = new OrderRequest([$this->createPosition()], 85, 1);
        $service->createOrder($request);

        $requests = $mockClient->getRequests();
        // auth + checkout POST + label download + manifest download = 4
        self::assertCount(4, $requests);

        // Auth request
        self::assertSame('POST', $requests[0]->getMethod());
        self::assertStringContainsString('/user', (string) $requests[0]->getUri());

        // Checkout request
        self::assertSame('POST', $requests[1]->getMethod());
        self::assertStringContainsString('/app/shoppingcart/pdf', (string) $requests[1]->getUri());
        self::assertStringContainsString('directCheckout=true', (string) $requests[1]->getUri());

        // Label + manifest downloads use GET
        self::assertSame('GET', $requests[2]->getMethod());
        self::assertSame('GET', $requests[3]->getMethod());
    }

    #[Test]
    public function createPngOrderReturnsParsedOrder(): void
    {
        $mockClient = new MockClient();
        foreach (OrderTestProvider::checkoutPngSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createOrderService();
        $request = new PngOrderRequest([$this->createPosition()], 85);
        $order = $service->createPngOrder($request);

        self::assertInstanceOf(OrderInterface::class, $order);
        self::assertSame('98276338', $order->getShopOrderId());
        self::assertSame(42270, $order->getWalletBalance());
        self::assertNull($order->getManifest());
        self::assertCount(1, $order->getVouchers());
    }

    #[Test]
    public function createPngOrderSendsCorrectRequests(): void
    {
        $mockClient = new MockClient();
        foreach (OrderTestProvider::checkoutPngSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createOrderService();
        $request = new PngOrderRequest([$this->createPosition()], 85);
        $service->createPngOrder($request);

        $requests = $mockClient->getRequests();
        // auth + checkout POST + label download = 3 (no manifest)
        self::assertCount(3, $requests);

        $checkoutRequest = $requests[1];
        self::assertSame('POST', $checkoutRequest->getMethod());
        self::assertStringContainsString('/app/shoppingcart/png', (string) $checkoutRequest->getUri());
        self::assertStringContainsString('directCheckout=true', (string) $checkoutRequest->getUri());
    }

    #[Test]
    public function getOrderRetrievesExistingOrder(): void
    {
        $mockClient = new MockClient();
        foreach (OrderTestProvider::retrieveOrderSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createOrderService();
        $order = $service->getOrder('98276337');

        self::assertInstanceOf(OrderInterface::class, $order);
        self::assertSame('98276337', $order->getShopOrderId());
        self::assertNull($order->getManifest()); // empty manifestLink
        self::assertNotEmpty($order->getLabel());
    }

    #[Test]
    public function getOrderSendsGetRequest(): void
    {
        $mockClient = new MockClient();
        foreach (OrderTestProvider::retrieveOrderSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createOrderService();
        $service->getOrder('98276337');

        $requests = $mockClient->getRequests();
        self::assertCount(3, $requests); // auth + GET order + label download

        $orderRequest = $requests[1];
        self::assertSame('GET', $orderRequest->getMethod());
        self::assertStringContainsString('/app/shoppingcart/98276337', (string) $orderRequest->getUri());
    }

    #[Test]
    public function previewPdfOrderSendsValidateParam(): void
    {
        $mockClient = new MockClient();
        foreach (OrderTestProvider::checkoutPdfSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createOrderService();
        $request = new PdfPreviewRequest(VoucherLayout::AddressZone, 10001, 42, 1, Dpi::Dpi300);
        $service->previewPdfOrder($request);

        $previewRequest = $mockClient->getRequests()[1];
        self::assertSame('POST', $previewRequest->getMethod());
        self::assertStringContainsString('/app/shoppingcart/pdf', (string) $previewRequest->getUri());
        self::assertStringContainsString('validate=true', (string) $previewRequest->getUri());
        self::assertStringNotContainsString('directCheckout', (string) $previewRequest->getUri());
    }

    #[Test]
    public function previewPngOrderSendsValidateParam(): void
    {
        $mockClient = new MockClient();
        foreach (OrderTestProvider::checkoutPdfSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createOrderService();
        $request = new PngPreviewRequest(VoucherLayout::AddressZone, 10001);
        $service->previewPngOrder($request);

        $previewRequest = $mockClient->getRequests()[1];
        self::assertSame('POST', $previewRequest->getMethod());
        self::assertStringContainsString('/app/shoppingcart/png', (string) $previewRequest->getUri());
        self::assertStringContainsString('validate=true', (string) $previewRequest->getUri());
    }

    #[Test]
    public function contractValidation(): void
    {
        $mockClient = new MockClient();
        foreach (OrderTestProvider::initCartSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createOrderService();
        $service->initializeCart();

        $requests = $mockClient->getRequests();
        self::assertRequestMatchesSpec($requests[0]); // auth
        self::assertRequestMatchesSpec($requests[1]); // initCart
    }
}
