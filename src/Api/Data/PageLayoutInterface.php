<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api\Data;

/**
 * Page layout with orientation, dimensions, label grid, and margins.
 *
 * @api
 */
interface PageLayoutInterface
{
    public function getOrientation(): string;

    public function getSize(): LayoutDimensionInterface;

    public function getLabelCount(): LabelCountInterface;

    public function getLabelSpacing(): LayoutDimensionInterface;

    public function getMargin(): MarginInterface;
}
