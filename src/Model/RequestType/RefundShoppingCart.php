<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model\RequestType;

use DeutschePost\Sdk\Internetmarke\Model\RefundVoucher;

/**
 * Shopping cart wrapper for refund requests.
 *
 * @internal
 */
readonly class RefundShoppingCart implements \JsonSerializable
{
    /**
     * @param RefundVoucher[] $voucherList
     */
    public function __construct(
        private ?string $shopOrderId,
        private array $voucherList,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
