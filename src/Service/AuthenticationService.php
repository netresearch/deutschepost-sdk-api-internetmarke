<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Service;

use DeutschePost\Sdk\Internetmarke\Api\AuthenticationServiceInterface;
use DeutschePost\Sdk\Internetmarke\Exception\AuthenticationException;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceException;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceExceptionFactory;
use DeutschePost\Sdk\Internetmarke\Api\Data\AuthTokenInterface;
use DeutschePost\Sdk\Internetmarke\Model\AuthToken;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Acquires Bearer tokens via form-encoded POST to /user.
 *
 * @internal
 */
readonly class AuthenticationService implements AuthenticationServiceInterface
{
    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private string $baseUrl,
    ) {
    }

    /**
     * @throws AuthenticationException On invalid credentials (401).
     * @throws ServiceException On other API errors.
     */
    public function authenticate(
        string $clientId,
        string $clientSecret,
        string $username,
        string $password,
    ): AuthTokenInterface {
        try {
            // Non-standard OAuth hybrid: uses client_credentials grant type
            // but includes username/password. The API requires all four values
            // in a single form-encoded POST rather than a standard two-step flow.
            $body = http_build_query([
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'username' => $username,
                'password' => $password,
            ]);

            $request = $this->requestFactory->createRequest('POST', $this->baseUrl . '/user')
                ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withBody($this->streamFactory->createStream($body));

            $response = $this->client->sendRequest($request);
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            return new AuthToken(
                (string) ($data['access_token'] ?? ''),
                (int) ($data['walletBalance'] ?? 0),
                (int) ($data['expires_in'] ?? 0),
                (string) ($data['external_customer_id'] ?? ''),
                (string) ($data['authenticated_user'] ?? ''),
                (string) ($data['token_type'] ?? ''),
                (string) ($data['issued_at'] ?? ''),
                (string) ($data['infoMessage'] ?? ''),
            );
        } catch (\Throwable $e) {
            throw ServiceExceptionFactory::create($e);
        }
    }
}
