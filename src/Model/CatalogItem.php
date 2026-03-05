<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model;

use DeutschePost\Sdk\Internetmarke\Api\Data\CatalogItemInterface;

/**
 * Catalog category containing motif images.
 *
 * Not declared readonly: mutable properties provide safe defaults for optional API fields that JsonMapper may not populate.
 * Treat as immutable — the public API exposes only getters.
 *
 * @api
 */
class CatalogItem implements CatalogItemInterface
{
    private ?string $category = null;
    private ?string $categoryDescription = null;
    private ?int $categoryId = null;

    /** @var \DeutschePost\Sdk\Internetmarke\Model\ImageItem[]|null */
    private ?array $images = null;

    public function getCategory(): string
    {
        return $this->category ?? '';
    }

    public function getCategoryDescription(): string
    {
        return $this->categoryDescription ?? '';
    }

    public function getCategoryId(): int
    {
        return $this->categoryId ?? 0;
    }

    /**
     * @return ImageItem[]
     */
    public function getImages(): array
    {
        return $this->images ?? [];
    }
}
