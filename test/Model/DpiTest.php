<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Model;

use DeutschePost\Sdk\Internetmarke\Api\Dpi;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DpiTest extends TestCase
{
    #[Test]
    public function dpi300HasCorrectValue(): void
    {
        self::assertSame('DPI300', Dpi::Dpi300->value);
    }

    #[Test]
    public function dpi203HasCorrectValue(): void
    {
        self::assertSame('DPI203', Dpi::Dpi203->value);
    }

    #[Test]
    public function canBeConstructedFromBackedValue(): void
    {
        self::assertSame(Dpi::Dpi300, Dpi::from('DPI300'));
        self::assertSame(Dpi::Dpi203, Dpi::from('DPI203'));
    }
}
