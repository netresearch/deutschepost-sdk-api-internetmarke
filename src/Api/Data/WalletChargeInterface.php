<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api\Data;

/**
 * Wallet charge response with order ID and updated balance.
 *
 * @api
 */
interface WalletChargeInterface
{
    public function getShopOrderId(): string;

    /**
     * Updated Portokasse wallet balance in euro cents after the charge.
     */
    public function getWalletBalance(): int;
}
