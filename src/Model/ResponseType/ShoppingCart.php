<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model\ResponseType;

use DeutschePost\Sdk\Internetmarke\Model\Voucher;

/**
 * Shopping cart from a checkout response.
 *
 * @internal
 */
class ShoppingCart
{
    private ?string $shopOrderId = null;

    /** @var \DeutschePost\Sdk\Internetmarke\Model\Voucher[]|null */
    private ?array $voucherList = null;

    public function getShopOrderId(): string
    {
        return $this->shopOrderId ?? '';
    }

    /**
     * @return Voucher[]
     */
    public function getVoucherList(): array
    {
        return $this->voucherList ?? [];
    }
}
