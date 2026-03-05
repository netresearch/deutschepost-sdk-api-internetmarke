<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Model;

use DeutschePost\Sdk\Internetmarke\Api\ShippingList;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ShippingListTest extends TestCase
{
    #[Test]
    public function noneHasCorrectValue(): void
    {
        self::assertSame('0', ShippingList::None->value);
    }

    #[Test]
    public function addressHasCorrectValue(): void
    {
        self::assertSame('1', ShippingList::Address->value);
    }

    #[Test]
    public function productHasCorrectValue(): void
    {
        self::assertSame('2', ShippingList::Product->value);
    }

    #[Test]
    public function canBeConstructedFromBackedValue(): void
    {
        self::assertSame(ShippingList::None, ShippingList::from('0'));
        self::assertSame(ShippingList::Address, ShippingList::from('1'));
        self::assertSame(ShippingList::Product, ShippingList::from('2'));
    }
}
