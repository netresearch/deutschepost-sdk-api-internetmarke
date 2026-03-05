<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Service;

use DeutschePost\Sdk\Internetmarke\Api\Data\OrderInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\VoucherInterface;
use DeutschePost\Sdk\Internetmarke\Api\Dpi;
use DeutschePost\Sdk\Internetmarke\Api\ShippingList;
use DeutschePost\Sdk\Internetmarke\Api\VoucherLayout;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceException;
use DeutschePost\Sdk\Internetmarke\Model\OrderRequest;
use DeutschePost\Sdk\Internetmarke\Model\PdfPreviewRequest;
use DeutschePost\Sdk\Internetmarke\Model\PngOrderRequest;
use DeutschePost\Sdk\Internetmarke\Model\PngPreviewRequest;
use DeutschePost\Sdk\Internetmarke\Model\RequestType\Address;
use DeutschePost\Sdk\Internetmarke\Model\RequestType\AddressBinding;
use DeutschePost\Sdk\Internetmarke\Model\ShoppingCartPosition;
use DeutschePost\Sdk\Internetmarke\Serializer\JsonSerializer;
use DeutschePost\Sdk\Internetmarke\Service\OrderService;
use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OrderServiceTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    private function createCheckoutResponse(): \Psr\Http\Message\ResponseInterface
    {
        $body = json_encode([
            'link' => 'https://api-eu.dhl.com/post/de/shipping/im/v1/app/shoppingcart/pdf/download123',
            'manifestLink' => 'https://api-eu.dhl.com/post/de/shipping/im/v1/app/shoppingcart/manifest/download123',
            'shoppingCart' => [
                'shopOrderId' => 'ORD-9876',
                'voucherList' => [
                    [
                        'voucherId' => 'A001ABCD1234',
                        'trackId' => 'TRK001234',
                    ],
                    [
                        'voucherId' => 'A001ABCD5678',
                        'trackId' => '',
                    ],
                ],
            ],
            'walletBallance' => 2446729,
        ], JSON_THROW_ON_ERROR);

        return $this->factory->createResponse(200)->withBody($this->factory->createStream($body));
    }

    private function createPdfDownloadResponse(): \Psr\Http\Message\ResponseInterface
    {
        return $this->factory->createResponse(200)->withBody(
            $this->factory->createStream('%PDF-1.4 fake pdf content')
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

    private function createService(MockClient $apiClient, MockClient $downloadClient): OrderService
    {
        return new OrderService(
            $apiClient,
            $downloadClient,
            $this->factory,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );
    }

    #[Test]
    public function createOrderReturnsOrderWithVouchers(): void
    {
        $apiClient = new MockClient();
        $apiClient->addResponse($this->createCheckoutResponse());

        $downloadClient = new MockClient();
        $downloadClient->addResponse($this->createPdfDownloadResponse());
        $downloadClient->addResponse($this->createPdfDownloadResponse());

        $service = $this->createService($apiClient, $downloadClient);

        $request = new OrderRequest([$this->createPosition()], 85, 1);

        $order = $service->createOrder($request);

        self::assertInstanceOf(OrderInterface::class, $order);
        self::assertSame('ORD-9876', $order->getShopOrderId());
        self::assertSame(2446729, $order->getWalletBalance());
        self::assertStringContainsString('%PDF', $order->getLabel());
        self::assertStringContainsString('%PDF', $order->getManifest());

        $vouchers = $order->getVouchers();
        self::assertCount(2, $vouchers);
        self::assertContainsOnlyInstancesOf(VoucherInterface::class, $vouchers);

        self::assertSame('A001ABCD1234', $vouchers[0]->getVoucherId());
        self::assertSame('TRK001234', $vouchers[0]->getTrackId());

        // Empty trackId should be normalized to null
        self::assertSame('A001ABCD5678', $vouchers[1]->getVoucherId());
        self::assertNull($vouchers[1]->getTrackId());
    }

    #[Test]
    public function sendsDirectCheckoutPdfRequest(): void
    {
        $apiClient = new MockClient();
        $apiClient->addResponse($this->createCheckoutResponse());

        $downloadClient = new MockClient();
        $downloadClient->addResponse($this->createPdfDownloadResponse());
        $downloadClient->addResponse($this->createPdfDownloadResponse());

        $service = $this->createService($apiClient, $downloadClient);

        $request = new OrderRequest(
            [$this->createPosition()],
            85,
            1,
            true,
            ShippingList::None,
            Dpi::Dpi300,
        );

        $service->createOrder($request);

        $requests = $apiClient->getRequests();
        $checkoutRequest = $requests[0];

        self::assertSame('POST', $checkoutRequest->getMethod());
        self::assertStringContainsString('/app/shoppingcart/pdf', (string) $checkoutRequest->getUri());
        self::assertStringContainsString('directCheckout=true', (string) $checkoutRequest->getUri());

        $body = json_decode((string) $checkoutRequest->getBody(), true);
        self::assertSame('AppShoppingCartPDFRequest', $body['type']);
        self::assertSame(85, $body['total']);
        self::assertSame(1, $body['pageFormatId']);
        self::assertTrue($body['createManifest']);
        self::assertSame('0', $body['createShippingList']);
        self::assertSame('DPI300', $body['dpi']);
        self::assertCount(1, $body['positions']);
        self::assertSame('ADDRESS_ZONE', $body['positions'][0]['voucherLayout']);
    }

    #[Test]
    public function handlesAbsentManifestLink(): void
    {
        $body = json_encode([
            'link' => 'https://api.example.com/download/pdf',
            'shoppingCart' => [
                'shopOrderId' => 'ORD-1',
                'voucherList' => [
                    ['voucherId' => 'V1', 'trackId' => 'T1'],
                ],
            ],
            'walletBallance' => 100,
        ], JSON_THROW_ON_ERROR);

        $apiClient = new MockClient();
        $apiClient->addResponse(
            $this->factory->createResponse(200)->withBody($this->factory->createStream($body))
        );

        $downloadClient = new MockClient();
        $downloadClient->addResponse($this->createPdfDownloadResponse());

        $service = $this->createService($apiClient, $downloadClient);

        $request = new OrderRequest([$this->createPosition()], 85, 1);

        $order = $service->createOrder($request);

        self::assertNull($order->getManifest());
    }

    #[Test]
    public function handlesEmptyStringManifestLink(): void
    {
        $body = json_encode([
            'link' => 'https://api.example.com/download/pdf',
            'manifestLink' => '',
            'shoppingCart' => [
                'shopOrderId' => 'ORD-1',
                'voucherList' => [
                    ['voucherId' => 'V1', 'trackId' => 'T1'],
                ],
            ],
            'walletBallance' => 100,
        ], JSON_THROW_ON_ERROR);

        $apiClient = new MockClient();
        $apiClient->addResponse(
            $this->factory->createResponse(200)->withBody($this->factory->createStream($body))
        );

        $downloadClient = new MockClient();
        $downloadClient->addResponse($this->createPdfDownloadResponse());

        $service = $this->createService($apiClient, $downloadClient);

        $request = new OrderRequest([$this->createPosition()], 85, 1);

        $order = $service->createOrder($request);

        self::assertNull($order->getManifest());
        self::assertCount(1, $downloadClient->getRequests());
    }

    #[Test]
    public function defaultsWalletBalanceToZeroWhenMissing(): void
    {
        $body = json_encode([
            'link' => 'https://api.example.com/download/pdf',
            'shoppingCart' => [
                'shopOrderId' => 'ORD-1',
                'voucherList' => [
                    ['voucherId' => 'V1', 'trackId' => 'T1'],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $apiClient = new MockClient();
        $apiClient->addResponse(
            $this->factory->createResponse(200)->withBody($this->factory->createStream($body))
        );

        $downloadClient = new MockClient();
        $downloadClient->addResponse($this->createPdfDownloadResponse());

        $service = $this->createService($apiClient, $downloadClient);

        $request = new OrderRequest([$this->createPosition()], 85, 1);

        $order = $service->createOrder($request);

        self::assertSame(0, $order->getWalletBalance());
    }

    #[Test]
    public function throwsServiceExceptionOnApiError(): void
    {
        $apiClient = new MockClient();
        $apiClient->addException(new \RuntimeException('timeout'));

        $downloadClient = new MockClient();

        $service = $this->createService($apiClient, $downloadClient);

        $request = new OrderRequest([$this->createPosition()], 85, 1);

        $this->expectException(ServiceException::class);

        $service->createOrder($request);
    }

    #[Test]
    public function binaryDownloadDoesNotUseApiClient(): void
    {
        $apiClient = new MockClient();
        $apiClient->addResponse($this->createCheckoutResponse());

        $downloadClient = new MockClient();
        $downloadClient->addResponse($this->createPdfDownloadResponse());
        $downloadClient->addResponse($this->createPdfDownloadResponse());

        $service = $this->createService($apiClient, $downloadClient);

        $request = new OrderRequest([$this->createPosition()], 85, 1);

        $service->createOrder($request);

        // API client should only receive the checkout POST
        self::assertCount(1, $apiClient->getRequests());

        // Download client should receive the PDF and manifest fetches
        self::assertCount(2, $downloadClient->getRequests());
    }

    #[Test]
    public function throwsServiceExceptionOnFailedPdfDownload(): void
    {
        $apiClient = new MockClient();
        $apiClient->addResponse($this->createCheckoutResponse());

        $downloadClient = new MockClient();
        $downloadClient->addResponse($this->factory->createResponse(500));

        $service = $this->createService($apiClient, $downloadClient);

        $request = new OrderRequest([$this->createPosition()], 85, 1);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('download');

        $service->createOrder($request);
    }

    #[Test]
    public function voucherTrackIdIsNullWhenMissing(): void
    {
        $body = json_encode([
            'link' => 'https://api.example.com/download/pdf',
            'shoppingCart' => [
                'shopOrderId' => 'ORD-1',
                'voucherList' => [
                    ['voucherId' => 'V1'],
                ],
            ],
            'walletBallance' => 100,
        ], JSON_THROW_ON_ERROR);

        $apiClient = new MockClient();
        $apiClient->addResponse(
            $this->factory->createResponse(200)->withBody($this->factory->createStream($body))
        );

        $downloadClient = new MockClient();
        $downloadClient->addResponse($this->createPdfDownloadResponse());

        $service = $this->createService($apiClient, $downloadClient);

        $request = new OrderRequest([$this->createPosition()], 85, 1);

        $order = $service->createOrder($request);
        $vouchers = $order->getVouchers();

        self::assertNull($vouchers[0]->getTrackId());
    }

    // --- initializeCart tests ---

    #[Test]
    public function initializeCartReturnsShopOrderId(): void
    {
        $responseBody = json_encode([
            'shopOrderId' => 'CART-42',
        ], JSON_THROW_ON_ERROR);

        $apiClient = new MockClient();
        $apiClient->addResponse(
            $this->factory->createResponse(200)->withBody($this->factory->createStream($responseBody))
        );

        $service = $this->createService($apiClient, new MockClient());

        $shopOrderId = $service->initializeCart();

        self::assertSame('CART-42', $shopOrderId);
    }

    #[Test]
    public function initializeCartSendsEmptyPost(): void
    {
        $responseBody = json_encode([
            'shopOrderId' => 'CART-1',
        ], JSON_THROW_ON_ERROR);

        $apiClient = new MockClient();
        $apiClient->addResponse(
            $this->factory->createResponse(200)->withBody($this->factory->createStream($responseBody))
        );

        $service = $this->createService($apiClient, new MockClient());

        $service->initializeCart();

        $lastRequest = $apiClient->getLastRequest();
        self::assertSame('POST', $lastRequest->getMethod());
        $uri = (string) $lastRequest->getUri();
        self::assertStringContainsString('/app/shoppingcart', $uri);
        self::assertStringNotContainsString('/pdf', $uri);
        self::assertStringNotContainsString('/png', $uri);
        self::assertStringNotContainsString('directCheckout', $uri);

        self::assertSame('', (string) $lastRequest->getBody());
    }

    #[Test]
    public function initializeCartThrowsServiceExceptionOnError(): void
    {
        $apiClient = new MockClient();
        $apiClient->addException(new \RuntimeException('timeout'));

        $service = $this->createService($apiClient, new MockClient());

        $this->expectException(ServiceException::class);

        $service->initializeCart();
    }

    // --- shopOrderId tests ---

    #[Test]
    public function shopOrderIdAppearsInPdfRequestBodyWhenSet(): void
    {
        $apiClient = new MockClient();
        $apiClient->addResponse($this->createCheckoutResponse());

        $downloadClient = new MockClient();
        $downloadClient->addResponse($this->createPdfDownloadResponse());
        $downloadClient->addResponse($this->createPdfDownloadResponse());

        $service = $this->createService($apiClient, $downloadClient);

        $request = new OrderRequest([$this->createPosition()], 85, 1, shopOrderId: 'CART-42');

        $service->createOrder($request);

        $body = json_decode((string) $apiClient->getLastRequest()->getBody(), true);
        self::assertSame('CART-42', $body['shopOrderId']);
    }

    #[Test]
    public function shopOrderIdOmittedFromPdfRequestBodyWhenNull(): void
    {
        $apiClient = new MockClient();
        $apiClient->addResponse($this->createCheckoutResponse());

        $downloadClient = new MockClient();
        $downloadClient->addResponse($this->createPdfDownloadResponse());
        $downloadClient->addResponse($this->createPdfDownloadResponse());

        $service = $this->createService($apiClient, $downloadClient);

        $request = new OrderRequest([$this->createPosition()], 85, 1);

        $service->createOrder($request);

        $body = json_decode((string) $apiClient->getLastRequest()->getBody(), true);
        self::assertArrayNotHasKey('shopOrderId', $body);
    }

    #[Test]
    public function shopOrderIdAppearsInPngRequestBodyWhenSet(): void
    {
        $apiClient = new MockClient();
        $apiClient->addResponse($this->createCheckoutResponse());

        $downloadClient = new MockClient();
        $downloadClient->addResponse($this->createPdfDownloadResponse());
        $downloadClient->addResponse($this->createPdfDownloadResponse());

        $service = $this->createService($apiClient, $downloadClient);

        $request = new PngOrderRequest([$this->createPosition()], 85, shopOrderId: 'CART-PNG-42');

        $service->createPngOrder($request);

        $body = json_decode((string) $apiClient->getLastRequest()->getBody(), true);
        self::assertSame('CART-PNG-42', $body['shopOrderId']);
    }

    // --- createPngOrder tests ---

    #[Test]
    public function createPngOrderReturnsOrder(): void
    {
        $apiClient = new MockClient();
        $apiClient->addResponse($this->createCheckoutResponse());

        $downloadClient = new MockClient();
        $downloadClient->addResponse($this->createPdfDownloadResponse());
        $downloadClient->addResponse($this->createPdfDownloadResponse());

        $service = $this->createService($apiClient, $downloadClient);

        $request = new PngOrderRequest([$this->createPosition()], 85);

        $order = $service->createPngOrder($request);

        self::assertInstanceOf(OrderInterface::class, $order);
        self::assertSame('ORD-9876', $order->getShopOrderId());
        self::assertSame(2446729, $order->getWalletBalance());
        self::assertCount(2, $order->getVouchers());
    }

    #[Test]
    public function createPngOrderSendsCorrectRequest(): void
    {
        $apiClient = new MockClient();
        $apiClient->addResponse($this->createCheckoutResponse());

        $downloadClient = new MockClient();
        $downloadClient->addResponse($this->createPdfDownloadResponse());
        $downloadClient->addResponse($this->createPdfDownloadResponse());

        $service = $this->createService($apiClient, $downloadClient);

        $request = new PngOrderRequest(
            [$this->createPosition()],
            85,
            true,
            ShippingList::None,
            Dpi::Dpi300,
            true,
        );

        $service->createPngOrder($request);

        $lastRequest = $apiClient->getLastRequest();
        self::assertSame('POST', $lastRequest->getMethod());
        self::assertStringContainsString('/app/shoppingcart/png', (string) $lastRequest->getUri());
        self::assertStringContainsString('directCheckout=true', (string) $lastRequest->getUri());

        $body = json_decode((string) $lastRequest->getBody(), true);
        self::assertSame('AppShoppingCartPNGRequest', $body['type']);
        self::assertSame(85, $body['total']);
        self::assertTrue($body['createManifest']);
        self::assertTrue($body['optimizePNG']);
        self::assertArrayNotHasKey('pageFormatId', $body);
    }

    #[Test]
    public function createPngOrderThrowsServiceExceptionOnError(): void
    {
        $apiClient = new MockClient();
        $apiClient->addException(new \RuntimeException('timeout'));

        $service = $this->createService($apiClient, new MockClient());

        $request = new PngOrderRequest([$this->createPosition()], 85);

        $this->expectException(ServiceException::class);

        $service->createPngOrder($request);
    }

    // --- getOrder tests ---

    #[Test]
    public function getOrderReturnsOrder(): void
    {
        $apiClient = new MockClient();
        $apiClient->addResponse($this->createCheckoutResponse());

        $downloadClient = new MockClient();
        $downloadClient->addResponse($this->createPdfDownloadResponse());
        $downloadClient->addResponse($this->createPdfDownloadResponse());

        $service = $this->createService($apiClient, $downloadClient);

        $order = $service->getOrder('ORD-9876');

        self::assertInstanceOf(OrderInterface::class, $order);
        self::assertSame('ORD-9876', $order->getShopOrderId());
        self::assertSame(2446729, $order->getWalletBalance());
        self::assertNotEmpty($order->getLabel());
    }

    #[Test]
    public function getOrderSendsGetRequestWithOrderId(): void
    {
        $apiClient = new MockClient();
        $apiClient->addResponse($this->createCheckoutResponse());

        $downloadClient = new MockClient();
        $downloadClient->addResponse($this->createPdfDownloadResponse());
        $downloadClient->addResponse($this->createPdfDownloadResponse());

        $service = $this->createService($apiClient, $downloadClient);

        $service->getOrder('MY-ORDER-123');

        $lastRequest = $apiClient->getLastRequest();
        self::assertSame('GET', $lastRequest->getMethod());
        self::assertStringContainsString('/app/shoppingcart/MY-ORDER-123', (string) $lastRequest->getUri());
    }

    #[Test]
    public function getOrderUrlEncodesShopOrderId(): void
    {
        $apiClient = new MockClient();
        $apiClient->addResponse($this->createCheckoutResponse());

        $downloadClient = new MockClient();
        $downloadClient->addResponse($this->createPdfDownloadResponse());
        $downloadClient->addResponse($this->createPdfDownloadResponse());

        $service = $this->createService($apiClient, $downloadClient);

        $service->getOrder('ORD 12/34');

        $lastRequest = $apiClient->getLastRequest();
        self::assertStringContainsString('/app/shoppingcart/ORD%2012%2F34', (string) $lastRequest->getUri());
    }

    #[Test]
    public function getOrderThrowsServiceExceptionOnError(): void
    {
        $apiClient = new MockClient();
        $apiClient->addException(new \RuntimeException('connection refused'));

        $service = $this->createService($apiClient, new MockClient());

        $this->expectException(ServiceException::class);

        $service->getOrder('ORD-1');
    }

    // --- previewPdfOrder tests ---

    #[Test]
    public function previewPdfOrderSendsValidateRequest(): void
    {
        $apiClient = new MockClient();
        $apiClient->addResponse($this->createCheckoutResponse());

        $downloadClient = new MockClient();
        $downloadClient->addResponse($this->createPdfDownloadResponse());
        $downloadClient->addResponse($this->createPdfDownloadResponse());

        $service = $this->createService($apiClient, $downloadClient);

        $request = new PdfPreviewRequest(VoucherLayout::AddressZone, 10001, 42, 1, Dpi::Dpi300);

        $order = $service->previewPdfOrder($request);

        self::assertInstanceOf(OrderInterface::class, $order);

        $lastRequest = $apiClient->getLastRequest();
        self::assertSame('POST', $lastRequest->getMethod());

        $uri = (string) $lastRequest->getUri();
        self::assertStringContainsString('/app/shoppingcart/pdf', $uri);
        self::assertStringContainsString('validate=true', $uri);
        self::assertStringNotContainsString('directCheckout', $uri);

        $body = json_decode((string) $lastRequest->getBody(), true);
        self::assertSame('AppShoppingCartPreviewPDFRequest', $body['type']);
        self::assertSame('ADDRESS_ZONE', $body['voucherLayout']);
        self::assertSame(10001, $body['productCode']);
        self::assertSame(42, $body['imageID']);
        self::assertSame(1, $body['pageFormatId']);
        self::assertSame('DPI300', $body['dpi']);
        self::assertArrayNotHasKey('positions', $body);
        self::assertArrayNotHasKey('total', $body);
    }

    #[Test]
    public function previewPdfOrderOmitsNullFields(): void
    {
        $apiClient = new MockClient();
        $apiClient->addResponse($this->createCheckoutResponse());

        $downloadClient = new MockClient();
        $downloadClient->addResponse($this->createPdfDownloadResponse());
        $downloadClient->addResponse($this->createPdfDownloadResponse());

        $service = $this->createService($apiClient, $downloadClient);

        $request = new PdfPreviewRequest(VoucherLayout::FrankingZone, 10001);

        $service->previewPdfOrder($request);

        $body = json_decode((string) $apiClient->getLastRequest()->getBody(), true);
        self::assertSame('FRANKING_ZONE', $body['voucherLayout']);
        self::assertSame(10001, $body['productCode']);
        self::assertArrayNotHasKey('imageID', $body);
        self::assertArrayNotHasKey('pageFormatId', $body);
        self::assertArrayNotHasKey('dpi', $body);
    }

    #[Test]
    public function previewPdfOrderThrowsServiceExceptionOnError(): void
    {
        $apiClient = new MockClient();
        $apiClient->addException(new \RuntimeException('timeout'));

        $service = $this->createService($apiClient, new MockClient());

        $request = new PdfPreviewRequest(VoucherLayout::AddressZone, 10001);

        $this->expectException(ServiceException::class);

        $service->previewPdfOrder($request);
    }

    // --- previewPngOrder tests ---

    #[Test]
    public function previewPngOrderSendsValidateRequest(): void
    {
        $apiClient = new MockClient();
        $apiClient->addResponse($this->createCheckoutResponse());

        $downloadClient = new MockClient();
        $downloadClient->addResponse($this->createPdfDownloadResponse());
        $downloadClient->addResponse($this->createPdfDownloadResponse());

        $service = $this->createService($apiClient, $downloadClient);

        $request = new PngPreviewRequest(VoucherLayout::AddressZone, 10001, 42, Dpi::Dpi300, true);

        $order = $service->previewPngOrder($request);

        self::assertInstanceOf(OrderInterface::class, $order);

        $lastRequest = $apiClient->getLastRequest();
        self::assertSame('POST', $lastRequest->getMethod());

        $uri = (string) $lastRequest->getUri();
        self::assertStringContainsString('/app/shoppingcart/png', $uri);
        self::assertStringContainsString('validate=true', $uri);
        self::assertStringNotContainsString('directCheckout', $uri);

        $body = json_decode((string) $lastRequest->getBody(), true);
        self::assertSame('AppShoppingCartPreviewPNGRequest', $body['type']);
        self::assertSame('ADDRESS_ZONE', $body['voucherLayout']);
        self::assertSame(10001, $body['productCode']);
        self::assertSame(42, $body['imageID']);
        self::assertSame('DPI300', $body['dpi']);
        self::assertTrue($body['optimizePNG']);
        self::assertArrayNotHasKey('positions', $body);
        self::assertArrayNotHasKey('total', $body);
        self::assertArrayNotHasKey('pageFormatId', $body);
    }

    #[Test]
    public function previewPngOrderOmitsNullFields(): void
    {
        $apiClient = new MockClient();
        $apiClient->addResponse($this->createCheckoutResponse());

        $downloadClient = new MockClient();
        $downloadClient->addResponse($this->createPdfDownloadResponse());
        $downloadClient->addResponse($this->createPdfDownloadResponse());

        $service = $this->createService($apiClient, $downloadClient);

        $request = new PngPreviewRequest(VoucherLayout::FrankingZone, 10001);

        $service->previewPngOrder($request);

        $body = json_decode((string) $apiClient->getLastRequest()->getBody(), true);
        self::assertSame('FRANKING_ZONE', $body['voucherLayout']);
        self::assertSame(10001, $body['productCode']);
        self::assertArrayNotHasKey('imageID', $body);
        self::assertArrayNotHasKey('dpi', $body);
        self::assertArrayNotHasKey('optimizePNG', $body);
    }

    #[Test]
    public function previewPngOrderThrowsServiceExceptionOnError(): void
    {
        $apiClient = new MockClient();
        $apiClient->addException(new \RuntimeException('timeout'));

        $service = $this->createService($apiClient, new MockClient());

        $request = new PngPreviewRequest(VoucherLayout::AddressZone, 10001);

        $this->expectException(ServiceException::class);

        $service->previewPngOrder($request);
    }

    // --- enum integration tests ---

    #[Test]
    public function orderRequestAcceptsEnumParameters(): void
    {
        $apiClient = new MockClient();
        $apiClient->addResponse($this->createCheckoutResponse());

        $downloadClient = new MockClient();
        $downloadClient->addResponse($this->createPdfDownloadResponse());
        $downloadClient->addResponse($this->createPdfDownloadResponse());

        $service = $this->createService($apiClient, $downloadClient);

        $request = new OrderRequest(
            [$this->createPosition()],
            85,
            1,
            true,
            ShippingList::None,
            Dpi::Dpi300,
        );

        $service->createOrder($request);

        $body = json_decode((string) $apiClient->getLastRequest()->getBody(), true);
        self::assertSame('0', $body['createShippingList']);
        self::assertSame('DPI300', $body['dpi']);
    }

    #[Test]
    public function pngOrderRequestAcceptsEnumParameters(): void
    {
        $apiClient = new MockClient();
        $apiClient->addResponse($this->createCheckoutResponse());

        $downloadClient = new MockClient();
        $downloadClient->addResponse($this->createPdfDownloadResponse());
        $downloadClient->addResponse($this->createPdfDownloadResponse());

        $service = $this->createService($apiClient, $downloadClient);

        $request = new PngOrderRequest(
            [$this->createPosition()],
            85,
            false,
            ShippingList::Address,
            Dpi::Dpi203,
        );

        $service->createPngOrder($request);

        $body = json_decode((string) $apiClient->getLastRequest()->getBody(), true);
        self::assertSame('1', $body['createShippingList']);
        self::assertSame('DPI203', $body['dpi']);
    }

    #[Test]
    public function pdfPreviewRequestAcceptsEnumParameters(): void
    {
        $apiClient = new MockClient();
        $apiClient->addResponse($this->createCheckoutResponse());

        $downloadClient = new MockClient();
        $downloadClient->addResponse($this->createPdfDownloadResponse());
        $downloadClient->addResponse($this->createPdfDownloadResponse());

        $service = $this->createService($apiClient, $downloadClient);

        $request = new PdfPreviewRequest(
            VoucherLayout::FrankingZone,
            10001,
            42,
            1,
            Dpi::Dpi300,
        );

        $service->previewPdfOrder($request);

        $body = json_decode((string) $apiClient->getLastRequest()->getBody(), true);
        self::assertSame('FRANKING_ZONE', $body['voucherLayout']);
        self::assertSame('DPI300', $body['dpi']);
    }

    #[Test]
    public function pngPreviewRequestAcceptsEnumParameters(): void
    {
        $apiClient = new MockClient();
        $apiClient->addResponse($this->createCheckoutResponse());

        $downloadClient = new MockClient();
        $downloadClient->addResponse($this->createPdfDownloadResponse());
        $downloadClient->addResponse($this->createPdfDownloadResponse());

        $service = $this->createService($apiClient, $downloadClient);

        $request = new PngPreviewRequest(
            VoucherLayout::AddressZone,
            10001,
            null,
            Dpi::Dpi203,
        );

        $service->previewPngOrder($request);

        $body = json_decode((string) $apiClient->getLastRequest()->getBody(), true);
        self::assertSame('ADDRESS_ZONE', $body['voucherLayout']);
        self::assertSame('DPI203', $body['dpi']);
    }
}
