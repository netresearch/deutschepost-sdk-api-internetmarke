<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model\ResponseType;

use DeutschePost\Sdk\Internetmarke\Api\Data\LayoutDimensionInterface;

/**
 * Two-dimensional measurement (x, y) for page/label dimensions.
 *
 * Reused for page size and label spacing.
 *
 * @internal
 */
class LayoutDimension implements LayoutDimensionInterface
{
    private ?float $x = null;

    private ?float $y = null;

    public function getX(): float
    {
        return $this->x ?? 0.0;
    }

    public function getY(): float
    {
        return $this->y ?? 0.0;
    }
}
