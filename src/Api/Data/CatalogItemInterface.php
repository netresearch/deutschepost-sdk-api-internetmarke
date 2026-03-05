<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api\Data;

/**
 * Catalog category containing motif images.
 *
 * @api
 */
interface CatalogItemInterface
{
    public function getCategory(): string;

    public function getCategoryDescription(): string;

    public function getCategoryId(): int;

    /**
     * @return ImageItemInterface[]
     */
    public function getImages(): array;
}
