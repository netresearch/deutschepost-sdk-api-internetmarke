<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api;

/**
 * Shipping list type included with order checkout.
 *
 * @api
 */
enum ShippingList: string
{
    case None = '0';
    case Address = '1';
    case Product = '2';
}
