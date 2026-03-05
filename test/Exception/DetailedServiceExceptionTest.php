<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Exception;

use DeutschePost\Sdk\Internetmarke\Exception\DetailedServiceException;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DetailedServiceExceptionTest extends TestCase
{
    #[Test]
    public function isServiceException(): void
    {
        $exception = new DetailedServiceException('Bad Request');

        self::assertInstanceOf(ServiceException::class, $exception);
    }

    #[Test]
    public function storesMessageAndCode(): void
    {
        $exception = new DetailedServiceException('Bad Request: Invalid product code.', 400);

        self::assertSame('Bad Request: Invalid product code.', $exception->getMessage());
        self::assertSame(400, $exception->getCode());
    }

    #[Test]
    public function storesPreviousException(): void
    {
        $previous = new \RuntimeException('network error');
        $exception = new DetailedServiceException('Bad Gateway', 502, $previous);

        self::assertSame($previous, $exception->getPrevious());
    }
}
