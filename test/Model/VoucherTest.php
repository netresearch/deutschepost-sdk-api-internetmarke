<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Model;

use DeutschePost\Sdk\Internetmarke\Model\Voucher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class VoucherTest extends TestCase
{
    #[Test]
    public function throwsWhenVoucherIdIsNull(): void
    {
        $voucher = new Voucher();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('voucherId');
        $voucher->getVoucherId();
    }

    #[Test]
    public function returnsVoucherIdWhenPresent(): void
    {
        $voucher = new Voucher();
        // Use reflection to set private property (simulating JsonMapper)
        $reflection = new \ReflectionProperty($voucher, 'voucherId');
        $reflection->setValue($voucher, 'ABC123');
        self::assertSame('ABC123', $voucher->getVoucherId());
    }
}
