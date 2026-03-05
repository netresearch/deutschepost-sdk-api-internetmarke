<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model\ResponseType;

use DeutschePost\Sdk\Internetmarke\Api\Data\MarginInterface;

/**
 * Page margins in millimeters.
 *
 * @internal
 */
class Margin implements MarginInterface
{
    private ?float $top = null;

    private ?float $bottom = null;

    private ?float $left = null;

    private ?float $right = null;

    public function getTop(): float
    {
        return $this->top ?? 0.0;
    }

    public function getBottom(): float
    {
        return $this->bottom ?? 0.0;
    }

    public function getLeft(): float
    {
        return $this->left ?? 0.0;
    }

    public function getRight(): float
    {
        return $this->right ?? 0.0;
    }
}
