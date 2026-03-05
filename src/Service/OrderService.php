<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Service;

use DeutschePost\Sdk\Internetmarke\Api\Data\OrderInterface;
use DeutschePost\Sdk\Internetmarke\Api\OrderServiceInterface;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceException;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceExceptionFactory;
use DeutschePost\Sdk\Internetmarke\Model\Order;
use DeutschePost\Sdk\Internetmarke\Model\OrderRequest;
use DeutschePost\Sdk\Internetmarke\Model\ResponseType\ShoppingCart;
use DeutschePost\Sdk\Internetmarke\Model\PdfPreviewRequest;
use DeutschePost\Sdk\Internetmarke\Model\PngOrderRequest;
use DeutschePost\Sdk\Internetmarke\Model\PngPreviewRequest;
use DeutschePost\Sdk\Internetmarke\Model\ResponseType\CartInitResponse;
use DeutschePost\Sdk\Internetmarke\Model\ResponseType\CheckoutResponse;
use DeutschePost\Sdk\Internetmarke\Serializer\JsonSerializer;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Creates, retrieves, and manages voucher orders with PDF or PNG labels.
 *
 * The REST API returns download URLs for labels and manifests.
 * This service transparently fetches the binary content to maintain
 * interface compatibility with the module's expectations.
 *
 * @internal
 */
readonly class OrderService implements OrderServiceInterface
{
    public function __construct(
        private ClientInterface $client,
        private ClientInterface $downloadClient,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private JsonSerializer $serializer,
        private string $baseUrl,
    ) {
    }

    /**
     * @throws ServiceException
     */
    public function initializeCart(): string
    {
        try {
            $httpRequest = $this->requestFactory
                ->createRequest('POST', $this->baseUrl . '/app/shoppingcart');

            $response = $this->client->sendRequest($httpRequest);

            return $this->serializer->decode(
                (string) $response->getBody(),
                CartInitResponse::class,
            )->getShopOrderId();
        } catch (\Throwable $e) {
            throw ServiceExceptionFactory::create($e);
        }
    }

    /**
     * @throws ServiceException
     */
    public function createOrder(OrderRequest $request): OrderInterface
    {
        return $this->checkout('/app/shoppingcart/pdf?directCheckout=true', $request);
    }

    /**
     * @throws ServiceException
     */
    public function createPngOrder(PngOrderRequest $request): OrderInterface
    {
        return $this->checkout('/app/shoppingcart/png?directCheckout=true', $request);
    }

    /**
     * @throws ServiceException
     */
    public function previewPdfOrder(PdfPreviewRequest $request): OrderInterface
    {
        return $this->checkout('/app/shoppingcart/pdf?validate=true', $request);
    }

    /**
     * @throws ServiceException
     */
    public function previewPngOrder(PngPreviewRequest $request): OrderInterface
    {
        return $this->checkout('/app/shoppingcart/png?validate=true', $request);
    }

    /**
     * Submit a checkout request and build an order from the response.
     *
     * @throws ServiceException
     */
    private function checkout(string $path, \JsonSerializable $request): OrderInterface
    {
        try {
            $body = $this->serializer->encode($request);

            $httpRequest = $this->requestFactory
                ->createRequest('POST', $this->baseUrl . $path)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream($body));

            $response = $this->client->sendRequest($httpRequest);
            $checkout = $this->serializer->decode((string) $response->getBody(), CheckoutResponse::class);

            return $this->buildOrderFromCheckout($checkout);
        } catch (\Throwable $e) {
            throw ServiceExceptionFactory::create($e);
        }
    }

    /**
     * @throws ServiceException
     */
    public function getOrder(string $shopOrderId): OrderInterface
    {
        try {
            $httpRequest = $this->requestFactory
                ->createRequest('GET', $this->baseUrl . '/app/shoppingcart/' . rawurlencode($shopOrderId));

            $response = $this->client->sendRequest($httpRequest);
            $checkout = $this->serializer->decode((string) $response->getBody(), CheckoutResponse::class);

            return $this->buildOrderFromCheckout($checkout);
        } catch (\Throwable $e) {
            throw ServiceExceptionFactory::create($e);
        }
    }

    /**
     * Build an Order from a checkout response by downloading label and manifest binaries.
     *
     * Preview responses have no shopping cart (no purchase was made).
     */
    private function buildOrderFromCheckout(CheckoutResponse $checkout): Order
    {
        $label = $this->fetchBinary($checkout->getLink());
        $manifestLink = $checkout->getManifestLink();
        $manifest = ($manifestLink !== null && $manifestLink !== '')
            ? $this->fetchBinary($manifestLink)
            : null;

        $cart = $checkout->getShoppingCart();

        return new Order(
            $cart instanceof ShoppingCart ? $cart->getShopOrderId() : '',
            $cart instanceof ShoppingCart ? $cart->getVoucherList() : [],
            $label,
            $manifest,
            $checkout->getWalletBallance(),
        );
    }

    /**
     * Fetch binary content from a download URL.
     *
     * @throws ServiceException When the download fails or returns a non-2xx status.
     */
    private function fetchBinary(string $url): string
    {
        $request = $this->requestFactory->createRequest('GET', $url);
        $response = $this->downloadClient->sendRequest($request);

        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            throw ServiceExceptionFactory::wrapInServiceException(
                new \RuntimeException(
                    sprintf('Label download failed with HTTP %d', $statusCode),
                    $statusCode,
                ),
            );
        }

        return (string) $response->getBody();
    }
}
