<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Service;

use DeutschePost\Sdk\Internetmarke\Api\Data\UserProfileInterface;
use DeutschePost\Sdk\Internetmarke\Api\UserProfileServiceInterface;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceException;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceExceptionFactory;
use DeutschePost\Sdk\Internetmarke\Model\UserProfile;
use DeutschePost\Sdk\Internetmarke\Serializer\JsonSerializer;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * Retrieves the authenticated user's profile.
 *
 * @internal
 */
readonly class UserProfileService implements UserProfileServiceInterface
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
    public function getProfile(): UserProfileInterface
    {
        try {
            $request = $this->requestFactory->createRequest('GET', $this->baseUrl . '/user/profile');
            $response = $this->client->sendRequest($request);

            return $this->serializer->decode((string) $response->getBody(), UserProfile::class);
        } catch (\Throwable $e) {
            throw ServiceExceptionFactory::create($e);
        }
    }
}
