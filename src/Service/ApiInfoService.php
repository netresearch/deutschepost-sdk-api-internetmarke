<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Service;

use DeutschePost\Sdk\Internetmarke\Api\ApiInfoServiceInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\ApiInfoInterface;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceException;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceExceptionFactory;
use DeutschePost\Sdk\Internetmarke\Model\ResponseType\ApiInfoResponse;
use DeutschePost\Sdk\Internetmarke\Serializer\JsonSerializer;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * Retrieves API version and health status.
 *
 * @internal
 */
readonly class ApiInfoService implements ApiInfoServiceInterface
{
    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
        private JsonSerializer $serializer,
        private string $baseUrl,
    ) {
    }

    /**
     * @throws ServiceException
     */
    public function getInfo(): ApiInfoInterface
    {
        try {
            $request = $this->requestFactory->createRequest('GET', $this->baseUrl . '/');
            $response = $this->client->sendRequest($request);

            return $this->serializer->decode((string) $response->getBody(), ApiInfoResponse::class)->getAmp();
        } catch (\Throwable $e) {
            throw ServiceExceptionFactory::create($e);
        }
    }

    public function isHealthy(): bool
    {
        try {
            $this->getInfo();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
