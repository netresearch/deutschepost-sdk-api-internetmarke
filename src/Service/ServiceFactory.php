<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Service;

use DeutschePost\Sdk\Internetmarke\Api\ApiInfoServiceInterface;
use DeutschePost\Sdk\Internetmarke\Api\CatalogServiceInterface;
use DeutschePost\Sdk\Internetmarke\Api\OrderServiceInterface;
use DeutschePost\Sdk\Internetmarke\Api\RefundServiceInterface;
use DeutschePost\Sdk\Internetmarke\Api\ServiceFactoryInterface;
use DeutschePost\Sdk\Internetmarke\Api\UserProfileServiceInterface;
use DeutschePost\Sdk\Internetmarke\Api\WalletServiceInterface;
use DeutschePost\Sdk\Internetmarke\Auth\BearerTokenAuthentication;
use DeutschePost\Sdk\Internetmarke\Auth\TokenProvider;
use DeutschePost\Sdk\Internetmarke\Http\ClientPlugin\ErrorPlugin;
use DeutschePost\Sdk\Internetmarke\Http\RedactingHttpMessageFormatter;
use DeutschePost\Sdk\Internetmarke\Serializer\JsonSerializer;
use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Client\Common\Plugin\ContentLengthPlugin;
use Http\Client\Common\Plugin\HeaderDefaultsPlugin;
use Http\Client\Common\Plugin\LoggerPlugin;
use Http\Client\Common\PluginClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Primary SDK entry point. Assembles all infrastructure and provides
 * ready-to-use service instances.
 *
 * @api
 */
class ServiceFactory implements ServiceFactoryInterface
{
    private const string BASE_URL = 'https://api-eu.dhl.com/post/de/shipping/im/v1';

    private readonly ClientInterface $client;

    private readonly RequestFactoryInterface $requestFactory;

    private readonly StreamFactoryInterface $streamFactory;

    private readonly string $baseUrl;

    private readonly TokenProvider $tokenProvider;

    private readonly BearerTokenAuthentication $bearerAuth;

    private readonly JsonSerializer $serializer;

    public function __construct(
        string $clientId,
        string $clientSecret,
        string $username,
        string $password,
        private readonly LoggerInterface $logger = new NullLogger(),
        ?ClientInterface $client = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        string $baseUrl = self::BASE_URL,
    ) {
        $this->client = $client ?? Psr18ClientDiscovery::find();
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
        $this->baseUrl = $baseUrl;

        $authService = new AuthenticationService(
            $this->createAuthClient(),
            $this->requestFactory,
            $this->streamFactory,
            $this->baseUrl,
        );

        $this->tokenProvider = new TokenProvider(
            $authService,
            $clientId,
            $clientSecret,
            $username,
            $password,
        );

        $this->bearerAuth = new BearerTokenAuthentication($this->tokenProvider);
        $this->serializer = new JsonSerializer();
    }

    public function createApiInfoService(): ApiInfoServiceInterface
    {
        return new ApiInfoService(
            $this->createUnauthenticatedClient(),
            $this->requestFactory,
            $this->serializer,
            $this->baseUrl,
        );
    }

    public function createCatalogService(): CatalogServiceInterface
    {
        return new CatalogService(
            $this->createAuthenticatedClient(),
            $this->requestFactory,
            $this->serializer,
            $this->baseUrl,
        );
    }

    public function createOrderService(): OrderServiceInterface
    {
        return new OrderService(
            $this->createAuthenticatedClient(),
            $this->createDownloadClient(),
            $this->requestFactory,
            $this->streamFactory,
            $this->serializer,
            $this->baseUrl,
        );
    }

    public function createRefundService(): RefundServiceInterface
    {
        return new RefundService(
            $this->createAuthenticatedClient(),
            $this->requestFactory,
            $this->streamFactory,
            $this->serializer,
            $this->baseUrl,
        );
    }

    public function createUserProfileService(): UserProfileServiceInterface
    {
        return new UserProfileService(
            $this->createAuthenticatedClient(),
            $this->requestFactory,
            $this->serializer,
            $this->baseUrl,
        );
    }

    public function createWalletService(): WalletServiceInterface
    {
        return new WalletService(
            $this->createAuthenticatedClient(),
            $this->requestFactory,
            $this->serializer,
            $this->baseUrl,
        );
    }

    private function getUserAgent(): string
    {
        if (!class_exists('\\' . \Composer\InstalledVersions::class)) {
            return 'deutschepost-sdk-api-internetmarke';
        }

        try {
            return 'deutschepost-sdk-api-internetmarke/' . \Composer\InstalledVersions::getVersion('deutschepost/sdk-api-internetmarke');
        } catch (\OutOfBoundsException) {
            return 'deutschepost-sdk-api-internetmarke';
        }
    }

    /**
     * Client for authentication requests. Logging is intentionally omitted
     * because the request body contains plaintext credentials (client_secret,
     * password) that must not appear in logs.
     */
    private function createAuthClient(): ClientInterface
    {
        return new PluginClient($this->client, [
            new HeaderDefaultsPlugin([
                'Accept' => 'application/json',
                'User-Agent' => $this->getUserAgent(),
            ]),
            new ContentLengthPlugin(),
            new ErrorPlugin(),
        ]);
    }

    private function createUnauthenticatedClient(): ClientInterface
    {
        return new PluginClient($this->client, [
            new HeaderDefaultsPlugin([
                'Accept' => 'application/json',
                'User-Agent' => $this->getUserAgent(),
            ]),
            new ContentLengthPlugin(),
            new LoggerPlugin($this->logger, new RedactingHttpMessageFormatter()),
            new ErrorPlugin(),
        ]);
    }

    private function createAuthenticatedClient(): ClientInterface
    {
        return new PluginClient($this->client, [
            new HeaderDefaultsPlugin([
                'Accept' => 'application/json',
                'User-Agent' => $this->getUserAgent(),
            ]),
            new AuthenticationPlugin($this->bearerAuth),
            new ContentLengthPlugin(),
            new LoggerPlugin($this->logger, new RedactingHttpMessageFormatter()),
            new ErrorPlugin(),
        ]);
    }

    private function createDownloadClient(): ClientInterface
    {
        return new PluginClient($this->client, [
            new LoggerPlugin($this->logger, new RedactingHttpMessageFormatter()),
        ]);
    }
}
