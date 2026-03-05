<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model;

use DeutschePost\Sdk\Internetmarke\Api\Data\VoucherInterface;

/**
 * A purchased voucher from an order.
 *
 * Not declared readonly: mutable properties provide safe defaults for optional API fields that JsonMapper may not populate.
 * Treat as immutable — the public API exposes only getters.
 *
 * @api
 */
class Voucher implements VoucherInterface
{
    private ?string $voucherId = null;
    private ?string $trackId = null;

    public function getVoucherId(): string
    {
        return $this->voucherId ?? throw new \RuntimeException(
            'Required field voucherId is missing from the API response.',
        );
    }

    public function getTrackId(): ?string
    {
        // JsonMapper maps "" to "" for nullable strings; normalize to null
        return $this->trackId !== '' ? $this->trackId : null;
    }
}
