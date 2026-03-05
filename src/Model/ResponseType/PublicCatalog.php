<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model\ResponseType;

use DeutschePost\Sdk\Internetmarke\Model\CatalogItem;

/**
 * Public motif image catalog organized by category.
 *
 * @internal
 */
class PublicCatalog
{
    /** @var \DeutschePost\Sdk\Internetmarke\Model\CatalogItem[]|null */
    private ?array $items = null;

    /**
     * @return CatalogItem[]
     */
    public function getItems(): array
    {
        return $this->items ?? [];
    }
}
