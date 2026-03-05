<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Service;

use DeutschePost\Sdk\Internetmarke\Exception\AuthenticationException;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceException;
use DeutschePost\Sdk\Internetmarke\Http\ClientPlugin\ErrorPlugin;
use DeutschePost\Sdk\Internetmarke\Api\Data\AuthTokenInterface;
use DeutschePost\Sdk\Internetmarke\Service\AuthenticationService;
use Http\Client\Common\PluginClient;
use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AuthenticationServiceTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    private function createSuccessResponse(int $expiresIn = 3000): \Psr\Http\Message\ResponseInterface
    {
        $body = json_encode([
            'access_token' => 'BnN6L2test',
            'walletBalance' => 2446814,
            'token_type' => 'BearerToken',
            'expires_in' => $expiresIn,
            'issued_at' => 'Wed, 03 Apr 2024 08:37:17 GMT',
            'external_customer_id' => 'DHL-0123',
            'authenticated_user' => 'max.mustermann@deutschepost.de',
            'infoMessage' => 'Account expires soon',
            'userToken' => 'some-legacy-token',
        ], JSON_THROW_ON_ERROR);

        return $this->factory->createResponse(200)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->factory->createStream($body));
    }

    #[Test]
    public function authenticateReturnsAuthToken(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse($this->createSuccessResponse());

        $service = new AuthenticationService(
            $mockClient,
            $this->factory,
            $this->factory,
            'https://api-eu.dhl.com/post/de/shipping/im/v1'
        );

        $token = $service->authenticate('client-id', 'client-secret', 'user', 'pass');

        self::assertInstanceOf(AuthTokenInterface::class, $token);
        self::assertSame('BnN6L2test', $token->getAccessToken());
        self::assertSame(2446814, $token->getWalletBalance());
        self::assertSame(3000, $token->getExpiresIn());
        self::assertSame('DHL-0123', $token->getExternalCustomerId());
        self::assertSame('max.mustermann@deutschepost.de', $token->getAuthenticatedUser());
        self::assertSame('BearerToken', $token->getTokenType());
        self::assertSame('Wed, 03 Apr 2024 08:37:17 GMT', $token->getIssuedAt());
        self::assertSame('Account expires soon', $token->getInfoMessage());
    }

    #[Test]
    public function sendsFormEncodedPostRequest(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse($this->createSuccessResponse());

        $service = new AuthenticationService(
            $mockClient,
            $this->factory,
            $this->factory,
            'https://api-eu.dhl.com/post/de/shipping/im/v1'
        );

        $service->authenticate('my-client', 'my-secret', 'max@dp.de', 'p4ss');

        $lastRequest = $mockClient->getLastRequest();
        self::assertSame('POST', $lastRequest->getMethod());
        self::assertSame(
            'https://api-eu.dhl.com/post/de/shipping/im/v1/user',
            (string) $lastRequest->getUri()
        );
        self::assertStringContainsString(
            'application/x-www-form-urlencoded',
            $lastRequest->getHeaderLine('Content-Type')
        );

        $body = (string) $lastRequest->getBody();
        parse_str($body, $params);

        self::assertSame('client_credentials', $params['grant_type']);
        self::assertSame('my-client', $params['client_id']);
        self::assertSame('my-secret', $params['client_secret']);
        self::assertSame('max@dp.de', $params['username']);
        self::assertSame('p4ss', $params['password']);
    }

    #[Test]
    public function throwsAuthenticationExceptionOn401(): void
    {
        $errorBody = json_encode([
            'statusCode' => 401,
            'title' => 'Unauthorized',
            'detail' => 'Invalid credentials.',
        ], JSON_THROW_ON_ERROR);

        $mockClient = new MockClient();
        $mockClient->addResponse(
            $this->factory->createResponse(401)->withBody($this->factory->createStream($errorBody))
        );

        $service = new AuthenticationService(
            new PluginClient($mockClient, [new ErrorPlugin()]),
            $this->factory,
            $this->factory,
            'https://api-eu.dhl.com/post/de/shipping/im/v1'
        );

        $this->expectException(AuthenticationException::class);

        $service->authenticate('bad-id', 'bad-secret', 'user', 'pass');
    }

    #[Test]
    public function readsExpiresInFromResponse(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse($this->createSuccessResponse(86400));

        $service = new AuthenticationService(
            $mockClient,
            $this->factory,
            $this->factory,
            'https://api-eu.dhl.com/post/de/shipping/im/v1'
        );

        $token = $service->authenticate('id', 'secret', 'user', 'pass');

        self::assertSame(86400, $token->getExpiresIn());
    }

    #[Test]
    public function throwsServiceExceptionOnInvalidJsonResponse(): void
    {
        $response = $this->factory->createResponse(200)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->factory->createStream('not valid json{'));

        $mockClient = new MockClient();
        $mockClient->addResponse($response);

        $service = new AuthenticationService(
            $mockClient,
            $this->factory,
            $this->factory,
            'https://api-eu.dhl.com/post/de/shipping/im/v1'
        );

        $this->expectException(ServiceException::class);

        $service->authenticate('id', 'secret', 'user', 'pass');
    }
}
