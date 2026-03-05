<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api\Data;

/**
 * A purchased voucher from an order.
 *
 * @api
 */
interface VoucherInterface
{
    public function getVoucherId(): string;

    /**
     * Tracking ID for international shipments. Optional per API spec.
     */
    public function getTrackId(): ?string;
}
