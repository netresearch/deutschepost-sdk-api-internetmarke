<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Http\ClientPlugin;

use DeutschePost\Sdk\Internetmarke\Exception\AuthenticationErrorHttpException;
use DeutschePost\Sdk\Internetmarke\Exception\AuthenticationException;
use DeutschePost\Sdk\Internetmarke\Exception\DetailedErrorHttpException;
use DeutschePost\Sdk\Internetmarke\Exception\DetailedServiceException;
use DeutschePost\Sdk\Internetmarke\Http\ClientPlugin\ErrorPlugin;
use Http\Client\Common\PluginClient;
use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ErrorPluginTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    private function createPluginClient(MockClient $mockClient): PluginClient
    {
        return new PluginClient($mockClient, [new ErrorPlugin()]);
    }

    #[Test]
    public function passesSuccessfulResponseThrough(): void
    {
        $response = $this->factory->createResponse(200)->withBody(
            $this->factory->createStream('{"data": "ok"}')
        );

        $mockClient = new MockClient();
        $mockClient->addResponse($response);

        $client = $this->createPluginClient($mockClient);
        $request = $this->factory->createRequest('GET', 'https://api.example.com/');

        $result = $client->sendRequest($request);

        self::assertSame(200, $result->getStatusCode());
    }

    #[Test]
    public function throwsAuthenticationErrorOn401(): void
    {
        $body = json_encode([
            'statusCode' => 401,
            'title' => 'Unauthorized',
            'detail' => 'Invalid credentials.',
        ], JSON_THROW_ON_ERROR);

        $response = $this->factory->createResponse(401)->withBody(
            $this->factory->createStream($body)
        );

        $mockClient = new MockClient();
        $mockClient->addResponse($response);

        $client = $this->createPluginClient($mockClient);
        $request = $this->factory->createRequest('POST', 'https://api.example.com/user');

        $this->expectException(AuthenticationErrorHttpException::class);

        $client->sendRequest($request);
    }

    #[Test]
    public function errorResponseExposesDetail(): void
    {
        $body = json_encode([
            'statusCode' => 401,
            'title' => 'Unauthorized',
            'detail' => 'Invalid credentials.',
        ], JSON_THROW_ON_ERROR);

        $response = $this->factory->createResponse(401)->withBody(
            $this->factory->createStream($body)
        );

        $mockClient = new MockClient();
        $mockClient->addResponse($response);

        $client = $this->createPluginClient($mockClient);
        $request = $this->factory->createRequest('POST', 'https://api.example.com/user');

        try {
            $client->sendRequest($request);
            self::fail('Expected AuthenticationErrorHttpException');
        } catch (AuthenticationErrorHttpException $e) {
            self::assertInstanceOf(AuthenticationException::class, $e);
            self::assertStringContainsString('Unauthorized', $e->getMessage());
            self::assertStringContainsString('Invalid credentials.', $e->getMessage());
            self::assertSame(401, $e->getCode());
        }
    }

    #[Test]
    public function throwsDetailedErrorOnClientError(): void
    {
        $body = json_encode([
            'statusCode' => 400,
            'title' => 'Bad Request',
            'detail' => 'Invalid product code.',
        ], JSON_THROW_ON_ERROR);

        $response = $this->factory->createResponse(400)->withBody(
            $this->factory->createStream($body)
        );

        $mockClient = new MockClient();
        $mockClient->addResponse($response);

        $client = $this->createPluginClient($mockClient);
        $request = $this->factory->createRequest('POST', 'https://api.example.com/app/shoppingcart/pdf');

        try {
            $client->sendRequest($request);
            self::fail('Expected DetailedErrorHttpException');
        } catch (DetailedErrorHttpException $e) {
            self::assertInstanceOf(DetailedServiceException::class, $e);
            self::assertStringContainsString('Bad Request', $e->getMessage());
            self::assertStringContainsString('Invalid product code.', $e->getMessage());
            self::assertSame(400, $e->getCode());
        }
    }

    #[Test]
    public function throwsDetailedErrorOnServerError(): void
    {
        $response = $this->factory->createResponse(500)->withBody(
            $this->factory->createStream('Internal Server Error')
        );

        $mockClient = new MockClient();
        $mockClient->addResponse($response);

        $client = $this->createPluginClient($mockClient);
        $request = $this->factory->createRequest('GET', 'https://api.example.com/');

        $this->expectException(DetailedErrorHttpException::class);

        $client->sendRequest($request);
    }

    #[Test]
    public function nonJsonErrorPreservesStatusCode(): void
    {
        $response = $this->factory->createResponse(502)->withBody(
            $this->factory->createStream('<html>Bad Gateway</html>')
        );

        $mockClient = new MockClient();
        $mockClient->addResponse($response);

        $client = $this->createPluginClient($mockClient);
        $request = $this->factory->createRequest('GET', 'https://api.example.com/');

        try {
            $client->sendRequest($request);
            self::fail('Expected DetailedErrorHttpException');
        } catch (DetailedErrorHttpException $e) {
            self::assertInstanceOf(DetailedServiceException::class, $e);
            self::assertSame(502, $e->getCode());
        }
    }

    #[Test]
    public function passesRedirectResponseThrough(): void
    {
        $response = $this->factory->createResponse(301)
            ->withHeader('Location', 'https://api.example.com/new-location');

        $mockClient = new MockClient();
        $mockClient->addResponse($response);

        $client = $this->createPluginClient($mockClient);
        $request = $this->factory->createRequest('GET', 'https://api.example.com/');

        $result = $client->sendRequest($request);

        self::assertSame(301, $result->getStatusCode());
    }

    #[Test]
    public function throwsAuthenticationErrorOn403(): void
    {
        $body = json_encode([
            'statusCode' => 403,
            'title' => 'Forbidden',
            'detail' => 'Insufficient scope.',
        ], JSON_THROW_ON_ERROR);

        $response = $this->factory->createResponse(403)->withBody(
            $this->factory->createStream($body)
        );

        $mockClient = new MockClient();
        $mockClient->addResponse($response);

        $client = $this->createPluginClient($mockClient);
        $request = $this->factory->createRequest('GET', 'https://api.example.com/');

        try {
            $client->sendRequest($request);
            self::fail('Expected AuthenticationErrorHttpException');
        } catch (AuthenticationErrorHttpException $e) {
            self::assertInstanceOf(AuthenticationException::class, $e);
            self::assertStringContainsString('Forbidden', $e->getMessage());
            self::assertStringContainsString('Insufficient scope.', $e->getMessage());
            self::assertSame(403, $e->getCode());
        }
    }

    #[Test]
    public function propagatesClientExceptions(): void
    {
        $mockClient = new MockClient();
        $mockClient->addException(new \RuntimeException('Connection refused'));

        $client = $this->createPluginClient($mockClient);
        $request = $this->factory->createRequest('GET', 'https://api.example.com/');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection refused');

        $client->sendRequest($request);
    }
}
