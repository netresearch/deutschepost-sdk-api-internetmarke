<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model;

use DeutschePost\Sdk\Internetmarke\Api\Data\RetoureStateInterface;

/**
 * Retoure transaction state with voucher details.
 *
 * Not declared readonly: mutable properties provide safe defaults for optional API fields that JsonMapper may not populate.
 * Treat as immutable — the public API exposes only getters.
 *
 * @api
 */
class RetoureState implements RetoureStateInterface
{
    private ?int $retoureTransactionId = null;
    private ?string $shopRetoureId = null;
    private ?int $totalCount = null;
    private ?int $countStillOpen = null;
    private ?int $retourePrice = null;
    private ?string $creationDate = null;
    private ?string $serialnumber = null;

    /** @var Voucher[]|null */
    private ?array $refundedVouchers = null;

    /** @var Voucher[]|null */
    private ?array $notRefundedVouchers = null;

    public function getRetoureTransactionId(): string
    {
        return (string) ($this->retoureTransactionId ?? 0);
    }

    public function getShopRetoureId(): string
    {
        return $this->shopRetoureId ?? '';
    }

    public function getTotalCount(): int
    {
        return $this->totalCount ?? 0;
    }

    public function getCountStillOpen(): int
    {
        return $this->countStillOpen ?? 0;
    }

    public function getRetourePrice(): int
    {
        return $this->retourePrice ?? 0;
    }

    public function getCreationDate(): ?\DateTimeImmutable
    {
        if ($this->creationDate === null || $this->creationDate === '') {
            return null;
        }

        // Standard ISO 8601 formats (e.g. "2024-04-03T08:37:17Z")
        $date = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:sP', $this->creationDate)
            ?: \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.vP', $this->creationDate);

        if ($date !== false) {
            return $date;
        }

        // Non-standard API format: dMMYYYY-HHMMSS (day without leading zero)
        // Example: "6102025-190339" = 6th October 2025, 19:03:39
        if (preg_match('/^(\d{1,2})(\d{2})(\d{4})-(\d{2})(\d{2})(\d{2})$/', $this->creationDate, $m)) {
            return new \DateTimeImmutable(sprintf(
                '%04d-%02d-%02dT%02d:%02d:%02d',
                (int) $m[3],
                (int) $m[2],
                (int) $m[1],
                (int) $m[4],
                (int) $m[5],
                (int) $m[6],
            ));
        }

        return null;
    }

    public function getSerialnumber(): string
    {
        return $this->serialnumber ?? '';
    }

    /**
     * @return Voucher[]
     */
    public function getRefundedVouchers(): array
    {
        return $this->refundedVouchers ?? [];
    }

    /**
     * @return Voucher[]
     */
    public function getNotRefundedVouchers(): array
    {
        return $this->notRefundedVouchers ?? [];
    }
}
