<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Exception;

use DeutschePost\Sdk\Internetmarke\Exception\AuthenticationErrorHttpException;
use DeutschePost\Sdk\Internetmarke\Exception\AuthenticationException;
use Http\Client\Exception as HttpClientException;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AuthenticationErrorHttpExceptionTest extends TestCase
{
    #[Test]
    public function isAuthenticationException(): void
    {
        $exception = new AuthenticationErrorHttpException(
            'Unauthorized',
            new Request('POST', '/token'),
            new Response(401),
        );

        self::assertInstanceOf(AuthenticationException::class, $exception);
    }

    #[Test]
    public function isHttpClientException(): void
    {
        $exception = new AuthenticationErrorHttpException(
            'Unauthorized',
            new Request('POST', '/token'),
            new Response(401),
        );

        self::assertInstanceOf(HttpClientException::class, $exception);
    }

    #[Test]
    public function storesResponseStatusAsCode(): void
    {
        $exception = new AuthenticationErrorHttpException(
            'Forbidden',
            new Request('GET', '/test'),
            new Response(403),
        );

        self::assertSame(403, $exception->getCode());
    }

    #[Test]
    public function exposesRequestAndResponse(): void
    {
        $request = new Request('POST', '/token');
        $response = new Response(401);

        $exception = new AuthenticationErrorHttpException('Unauthorized', $request, $response);

        self::assertSame('POST', $exception->getRequest()->getMethod());
        self::assertSame((string) $request->getUri(), (string) $exception->getRequest()->getUri());
        self::assertSame(401, $exception->getResponse()->getStatusCode());
    }

    #[Test]
    public function requestHasAuthorizationHeaderStripped(): void
    {
        $request = new Request(
            'GET',
            '/test',
            ['Authorization' => 'Bearer secret-token'],
        );
        $response = new Response(401);

        $exception = new AuthenticationErrorHttpException('Unauthorized', $request, $response);

        self::assertFalse($exception->getRequest()->hasHeader('Authorization'));
    }

    #[Test]
    public function requestPreservesOtherHeaders(): void
    {
        $request = new Request(
            'GET',
            '/test',
            [
                'Authorization' => 'Bearer secret-token',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        );
        $response = new Response(401);

        $exception = new AuthenticationErrorHttpException('Unauthorized', $request, $response);

        self::assertFalse($exception->getRequest()->hasHeader('Authorization'));
        self::assertTrue($exception->getRequest()->hasHeader('Content-Type'));
        self::assertTrue($exception->getRequest()->hasHeader('Accept'));
    }
}
