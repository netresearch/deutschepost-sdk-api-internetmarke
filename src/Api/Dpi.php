<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api;

/**
 * Print resolution for voucher images.
 *
 * @api
 */
enum Dpi: string
{
    case Dpi300 = 'DPI300';
    case Dpi203 = 'DPI203';
}
