<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model;

/**
 * A voucher to be refunded, identified by voucherId and trackId.
 *
 * @api
 */
readonly class RefundVoucher implements \JsonSerializable
{
    public function __construct(
        private string $voucherId,
        private ?string $trackId = null,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        return array_filter(
            get_object_vars($this),
            static fn (mixed $value): bool => $value !== null,
        );
    }
}
