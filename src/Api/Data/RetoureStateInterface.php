<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api\Data;

/**
 * Retoure transaction state with voucher details.
 *
 * @api
 */
interface RetoureStateInterface
{
    /**
     * The API schema types this field as integer in the RetoureState response
     * but as string in the Refund response. Normalized to string for consistency.
     */
    public function getRetoureTransactionId(): string;

    public function getShopRetoureId(): string;

    public function getTotalCount(): int;

    /**
     * Number of stamps not yet processed.
     */
    public function getCountStillOpen(): int;

    /**
     * Retoure price in euro cents.
     */
    public function getRetourePrice(): int;

    /**
     * Timestamp of creation.
     *
     * Returns null when the API does not provide a parseable date.
     */
    public function getCreationDate(): ?\DateTimeImmutable;

    /**
     * FrankierAccountId, 10 characters.
     */
    public function getSerialnumber(): string;

    /**
     * @return VoucherInterface[]
     */
    public function getRefundedVouchers(): array;

    /**
     * @return VoucherInterface[]
     */
    public function getNotRefundedVouchers(): array;
}
