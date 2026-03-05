<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model;

use DeutschePost\Sdk\Internetmarke\Api\Data\WalletChargeInterface;

/**
 * Wallet charge response with order ID and updated balance.
 *
 * Not declared readonly: mutable properties provide safe defaults for optional API fields that JsonMapper may not populate.
 * Treat as immutable — the public API exposes only getters.
 *
 * @api
 */
class WalletCharge implements WalletChargeInterface
{
    private ?string $shopOrderId = null;
    private ?int $walletBalance = null;

    public function getShopOrderId(): string
    {
        return $this->shopOrderId ?? '';
    }

    public function getWalletBalance(): int
    {
        return $this->walletBalance ?? 0;
    }
}
