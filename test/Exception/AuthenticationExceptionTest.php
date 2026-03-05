<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Exception;

use DeutschePost\Sdk\Internetmarke\Exception\AuthenticationException;
use DeutschePost\Sdk\Internetmarke\Exception\DetailedServiceException;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AuthenticationExceptionTest extends TestCase
{
    #[Test]
    public function isServiceException(): void
    {
        $exception = new AuthenticationException('Invalid credentials');

        self::assertInstanceOf(ServiceException::class, $exception);
    }

    #[Test]
    public function isDetailedServiceException(): void
    {
        $exception = new AuthenticationException('Unauthorized');

        self::assertInstanceOf(DetailedServiceException::class, $exception);
    }

    #[Test]
    public function storesMessageAndCode(): void
    {
        $exception = new AuthenticationException('Token expired', 401);

        self::assertSame('Token expired', $exception->getMessage());
        self::assertSame(401, $exception->getCode());
    }
}
