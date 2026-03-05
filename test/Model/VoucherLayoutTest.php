<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Model;

use DeutschePost\Sdk\Internetmarke\Api\VoucherLayout;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class VoucherLayoutTest extends TestCase
{
    #[Test]
    public function addressZoneHasCorrectValue(): void
    {
        self::assertSame('ADDRESS_ZONE', VoucherLayout::AddressZone->value);
    }

    #[Test]
    public function frankingZoneHasCorrectValue(): void
    {
        self::assertSame('FRANKING_ZONE', VoucherLayout::FrankingZone->value);
    }

    #[Test]
    public function canBeConstructedFromBackedValue(): void
    {
        self::assertSame(VoucherLayout::AddressZone, VoucherLayout::from('ADDRESS_ZONE'));
        self::assertSame(VoucherLayout::FrankingZone, VoucherLayout::from('FRANKING_ZONE'));
    }
}
