<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api\Data;

/**
 * Completed order with label PDF, vouchers, and wallet balance.
 *
 * @api
 */
interface OrderInterface
{
    public function getShopOrderId(): string;

    /**
     * @return VoucherInterface[]
     */
    public function getVouchers(): array;

    /**
     * Combined PDF label binary for all vouchers in this order.
     */
    public function getLabel(): string;

    /**
     * PDF manifest binary, if requested.
     */
    public function getManifest(): ?string;

    /**
     * Remaining Portokasse wallet balance in euro cents after this order.
     */
    public function getWalletBalance(): int;
}
