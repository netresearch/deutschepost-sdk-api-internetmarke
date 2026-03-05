<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Service;

use DeutschePost\Sdk\Internetmarke\Api\Data\WalletChargeInterface;
use DeutschePost\Sdk\Internetmarke\Api\WalletServiceInterface;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceException;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceExceptionFactory;
use DeutschePost\Sdk\Internetmarke\Model\WalletCharge;
use DeutschePost\Sdk\Internetmarke\Serializer\JsonSerializer;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * Charges the Portokasse wallet balance.
 *
 * @internal
 */
readonly class WalletService implements WalletServiceInterface
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
    public function chargeWallet(int $amount): WalletChargeInterface
    {
        try {
            $url = $this->baseUrl . '/app/wallet?' . http_build_query(['amount' => $amount]);

            $httpRequest = $this->requestFactory->createRequest('PUT', $url);

            $response = $this->client->sendRequest($httpRequest);

            return $this->serializer->decode((string) $response->getBody(), WalletCharge::class);
        } catch (\Throwable $e) {
            throw ServiceExceptionFactory::create($e);
        }
    }
}
