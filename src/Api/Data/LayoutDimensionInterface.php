<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api\Data;

/**
 * Two-dimensional measurement (x, y) for page/label dimensions.
 *
 * @api
 */
interface LayoutDimensionInterface
{
    public function getX(): float;

    public function getY(): float;
}
