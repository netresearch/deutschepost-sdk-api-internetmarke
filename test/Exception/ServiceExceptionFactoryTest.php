<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Exception;

use DeutschePost\Sdk\Internetmarke\Exception\AuthenticationException;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceException;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceExceptionFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ServiceExceptionFactoryTest extends TestCase
{
    #[Test]
    public function createPassesThroughServiceException(): void
    {
        $original = new ServiceException('Already an SDK exception', 42);
        $result = ServiceExceptionFactory::create($original);

        self::assertSame($original, $result);
    }

    #[Test]
    public function createPassesThroughServiceExceptionSubtype(): void
    {
        $original = new AuthenticationException('Auth failed', 401);
        $result = ServiceExceptionFactory::create($original);

        self::assertSame($original, $result);
        self::assertInstanceOf(AuthenticationException::class, $result);
    }

    #[Test]
    public function createWrapsGenericThrowable(): void
    {
        $previous = new \RuntimeException('Connection refused', 99);
        $result = ServiceExceptionFactory::create($previous);

        self::assertInstanceOf(ServiceException::class, $result);
        self::assertSame('Connection refused', $result->getMessage());
        self::assertSame(99, $result->getCode());
        self::assertSame($previous, $result->getPrevious());
    }

    #[Test]
    public function wrapInServiceExceptionWrapsThrowable(): void
    {
        $previous = new \RuntimeException('Something went wrong', 99);
        $exception = ServiceExceptionFactory::wrapInServiceException($previous);

        self::assertInstanceOf(ServiceException::class, $exception);
        self::assertSame('Something went wrong', $exception->getMessage());
        self::assertSame(99, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
    }
}
