<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model;

use DeutschePost\Sdk\Internetmarke\Model\RequestType\RefundShoppingCart;

/**
 * Request parameters for refunding vouchers.
 *
 * @api
 */
class RefundRequest implements \JsonSerializable
{
    private readonly RefundShoppingCart $shoppingCart;

    /**
     * @param RefundVoucher[] $vouchers
     */
    public function __construct(
        ?string $shopOrderId = null,
        array $vouchers = [],
    ) {
        if ($shopOrderId === null && $vouchers === []) {
            throw new \InvalidArgumentException(
                'At least one of shopOrderId or vouchers must be provided.'
            );
        }

        $this->shoppingCart = new RefundShoppingCart($shopOrderId, $vouchers);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
