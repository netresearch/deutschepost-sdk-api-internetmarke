<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Auth;

use DeutschePost\Sdk\Internetmarke\Auth\BearerTokenAuthentication;
use DeutschePost\Sdk\Internetmarke\Auth\TokenProvider;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BearerTokenAuthenticationTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    #[Test]
    public function addsAuthorizationHeader(): void
    {
        $tokenProvider = $this->createStub(TokenProvider::class);
        $tokenProvider->method('getToken')->willReturn('test-bearer-token');

        $auth = new BearerTokenAuthentication($tokenProvider);

        $request = $this->factory->createRequest('GET', 'https://api.example.com/app/catalog');
        $result = $auth->authenticate($request);

        self::assertSame('Bearer test-bearer-token', $result->getHeaderLine('Authorization'));
    }

    #[Test]
    public function callsTokenProviderOnEachRequest(): void
    {
        $tokenProvider = $this->createMock(TokenProvider::class);
        $tokenProvider->expects(self::exactly(2))
            ->method('getToken')
            ->willReturnOnConsecutiveCalls('token-1', 'token-2');

        $auth = new BearerTokenAuthentication($tokenProvider);

        $request = $this->factory->createRequest('GET', 'https://api.example.com/app/catalog');

        $result1 = $auth->authenticate($request);
        $result2 = $auth->authenticate($request);

        self::assertSame('Bearer token-1', $result1->getHeaderLine('Authorization'));
        self::assertSame('Bearer token-2', $result2->getHeaderLine('Authorization'));
    }

    #[Test]
    public function doesNotModifyOtherHeaders(): void
    {
        $tokenProvider = $this->createStub(TokenProvider::class);
        $tokenProvider->method('getToken')->willReturn('token');

        $auth = new BearerTokenAuthentication($tokenProvider);

        $request = $this->factory->createRequest('POST', 'https://api.example.com/app/shoppingcart/pdf')
            ->withHeader('Content-Type', 'application/json');

        $result = $auth->authenticate($request);

        self::assertSame('application/json', $result->getHeaderLine('Content-Type'));
        self::assertSame('Bearer token', $result->getHeaderLine('Authorization'));
    }
}
