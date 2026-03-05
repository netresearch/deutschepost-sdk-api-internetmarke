<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api\Data;

/**
 * Page margins in millimeters.
 *
 * @api
 */
interface MarginInterface
{
    public function getTop(): float;

    public function getBottom(): float;

    public function getLeft(): float;

    public function getRight(): float;
}
