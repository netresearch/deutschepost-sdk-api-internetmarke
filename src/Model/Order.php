<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model;

use DeutschePost\Sdk\Internetmarke\Api\Data\OrderInterface;

/**
 * Completed order with label PDF, vouchers, and wallet balance.
 *
 * @api
 */
readonly class Order implements OrderInterface
{
    /**
     * @param Voucher[] $vouchers
     * @param string $label Binary PDF content of the combined label sheet.
     * @param string|null $manifest Binary PDF content of the posting receipt, or null.
     * @param int $walletBalance Normalized wallet balance in cents (API field: walletBallance).
     */
    public function __construct(
        private string $shopOrderId,
        private array $vouchers,
        private string $label,
        private ?string $manifest,
        private int $walletBalance,
    ) {
    }

    public function getShopOrderId(): string
    {
        return $this->shopOrderId;
    }

    /**
     * @return Voucher[]
     */
    public function getVouchers(): array
    {
        return $this->vouchers;
    }

    /**
     * Combined label PDF (binary content).
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Posting receipt PDF (binary content), or null if not requested.
     */
    public function getManifest(): ?string
    {
        return $this->manifest;
    }

    /**
     * Wallet balance in euro cents after order.
     */
    public function getWalletBalance(): int
    {
        return $this->walletBalance;
    }
}
