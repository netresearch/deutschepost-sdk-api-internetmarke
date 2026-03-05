<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Model;

use DeutschePost\Sdk\Internetmarke\Model\RefundVoucher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RefundVoucherTest extends TestCase
{
    #[Test]
    public function serializesWithoutTrackId(): void
    {
        $voucher = new RefundVoucher('VOUCHER-123');
        $serialized = $voucher->jsonSerialize();

        self::assertSame(['voucherId' => 'VOUCHER-123'], $serialized);
        self::assertArrayNotHasKey('trackId', $serialized);
    }

    #[Test]
    public function serializesWithTrackId(): void
    {
        $voucher = new RefundVoucher('VOUCHER-123', 'TRACK-456');
        $serialized = $voucher->jsonSerialize();

        self::assertSame(
            ['voucherId' => 'VOUCHER-123', 'trackId' => 'TRACK-456'],
            $serialized,
        );
    }
}
