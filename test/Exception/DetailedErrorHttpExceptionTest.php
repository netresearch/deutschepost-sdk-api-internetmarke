<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Exception;

use DeutschePost\Sdk\Internetmarke\Exception\DetailedErrorHttpException;
use DeutschePost\Sdk\Internetmarke\Exception\DetailedServiceException;
use Http\Client\Exception as HttpClientException;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DetailedErrorHttpExceptionTest extends TestCase
{
    #[Test]
    public function isDetailedServiceException(): void
    {
        $exception = new DetailedErrorHttpException(
            'Bad Request',
            new Request('GET', '/test'),
            new Response(400),
        );

        self::assertInstanceOf(DetailedServiceException::class, $exception);
    }

    #[Test]
    public function isHttpClientException(): void
    {
        $exception = new DetailedErrorHttpException(
            'Bad Request',
            new Request('GET', '/test'),
            new Response(400),
        );

        self::assertInstanceOf(HttpClientException::class, $exception);
    }

    #[Test]
    public function storesResponseStatusAsCode(): void
    {
        $exception = new DetailedErrorHttpException(
            'Bad Request',
            new Request('GET', '/test'),
            new Response(400),
        );

        self::assertSame(400, $exception->getCode());
    }

    #[Test]
    public function exposesRequestAndResponse(): void
    {
        $request = new Request('POST', '/orders');
        $response = new Response(422);

        $exception = new DetailedErrorHttpException('Unprocessable', $request, $response);

        self::assertSame('POST', $exception->getRequest()->getMethod());
        self::assertSame((string) $request->getUri(), (string) $exception->getRequest()->getUri());
        self::assertSame(422, $exception->getResponse()->getStatusCode());
    }

    #[Test]
    public function requestHasAuthorizationHeaderStripped(): void
    {
        $request = new Request(
            'GET',
            '/test',
            ['Authorization' => 'Bearer secret-token'],
        );
        $response = new Response(400);

        $exception = new DetailedErrorHttpException('Bad Request', $request, $response);

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
        $response = new Response(400);

        $exception = new DetailedErrorHttpException('Bad Request', $request, $response);

        self::assertFalse($exception->getRequest()->hasHeader('Authorization'));
        self::assertTrue($exception->getRequest()->hasHeader('Content-Type'));
        self::assertTrue($exception->getRequest()->hasHeader('Accept'));
    }
}
