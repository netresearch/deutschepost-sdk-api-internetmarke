<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api;

/**
 * Voucher layout determines whether the franking mark is printed
 * in the address zone or franking zone of the label.
 *
 * @api
 */
enum VoucherLayout: string
{
    case AddressZone = 'ADDRESS_ZONE';
    case FrankingZone = 'FRANKING_ZONE';
}
