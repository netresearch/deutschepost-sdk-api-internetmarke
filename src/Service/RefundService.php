<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Service;

use DeutschePost\Sdk\Internetmarke\Api\Data\RefundInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\RetoureStateInterface;
use DeutschePost\Sdk\Internetmarke\Api\RefundServiceInterface;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceException;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceExceptionFactory;
use DeutschePost\Sdk\Internetmarke\Model\Refund;
use DeutschePost\Sdk\Internetmarke\Model\RefundRequest;
use DeutschePost\Sdk\Internetmarke\Model\ResponseType\RetoureStateResponse;
use DeutschePost\Sdk\Internetmarke\Serializer\JsonSerializer;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Requests refunds and queries retoure transaction state.
 *
 * @internal
 */
readonly class RefundService implements RefundServiceInterface
{
    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private JsonSerializer $serializer,
        private string $baseUrl,
    ) {
    }

    /**
     * @throws ServiceException
     */
    public function requestRefund(RefundRequest $request): RefundInterface
    {
        try {
            $body = $this->serializer->encode($request);

            $httpRequest = $this->requestFactory
                ->createRequest('POST', $this->baseUrl . '/app/retoure')
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream($body));

            $response = $this->client->sendRequest($httpRequest);

            return $this->serializer->decode((string) $response->getBody(), Refund::class);
        } catch (\Throwable $e) {
            throw ServiceExceptionFactory::create($e);
        }
    }

    /**
     * @return RetoureStateInterface[]
     * @throws ServiceException
     */
    public function getRetoureState(
        ?string $shopRetoureId = null,
        ?int $retoureTransactionId = null,
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null,
    ): array {
        try {
            $params = array_filter([
                'shopRetoureId' => $shopRetoureId,
                'retoureTransactionId' => $retoureTransactionId,
                'startDate' => $startDate?->format('Y-m-d\TH:i:s.vP'),
                'endDate' => $endDate?->format('Y-m-d\TH:i:s.vP'),
            ], static fn (int|string|null $value): bool => $value !== null);

            $url = $this->baseUrl . '/app/retoure';
            if ($params !== []) {
                $url .= '?' . http_build_query($params);
            }

            $request = $this->requestFactory->createRequest('GET', $url);
            $response = $this->client->sendRequest($request);

            return $this->serializer->decode(
                (string) $response->getBody(),
                RetoureStateResponse::class,
            )->getRetoureStates();
        } catch (\Throwable $e) {
            throw ServiceExceptionFactory::create($e);
        }
    }
}
