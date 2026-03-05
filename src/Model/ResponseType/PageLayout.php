<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model\ResponseType;

use DeutschePost\Sdk\Internetmarke\Api\Data\LabelCountInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\LayoutDimensionInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\MarginInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\PageLayoutInterface;

/**
 * Page layout structure with orientation, dimensions, label grid, and margins.
 *
 * Mirrors the nested pageLayout object from the catalog API response.
 *
 * @internal
 */
class PageLayout implements PageLayoutInterface
{
    private ?string $orientation = null;

    private ?LayoutDimension $size = null;

    private ?LabelCount $labelCount = null;

    private ?LayoutDimension $labelSpacing = null;

    private ?Margin $margin = null;

    public function getOrientation(): string
    {
        return $this->orientation ?? '';
    }

    public function getSize(): LayoutDimensionInterface
    {
        return $this->size ?? new LayoutDimension();
    }

    public function getLabelCount(): LabelCountInterface
    {
        return $this->labelCount ?? new LabelCount();
    }

    public function getLabelSpacing(): LayoutDimensionInterface
    {
        return $this->labelSpacing ?? new LayoutDimension();
    }

    public function getMargin(): MarginInterface
    {
        return $this->margin ?? new Margin();
    }
}
