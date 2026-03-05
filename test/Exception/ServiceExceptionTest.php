<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Exception;

use DeutschePost\Sdk\Internetmarke\Exception\ServiceException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ServiceExceptionTest extends TestCase
{
    #[Test]
    public function storesMessageAndCode(): void
    {
        $exception = new ServiceException('Something went wrong', 500);

        self::assertSame('Something went wrong', $exception->getMessage());
        self::assertSame(500, $exception->getCode());
    }

    #[Test]
    public function storesPreviousException(): void
    {
        $previous = new \RuntimeException('root cause');
        $exception = new ServiceException('Wrapper', 0, $previous);

        self::assertSame($previous, $exception->getPrevious());
    }
}
