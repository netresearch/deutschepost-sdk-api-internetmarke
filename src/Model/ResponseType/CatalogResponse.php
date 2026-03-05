<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model\ResponseType;

use DeutschePost\Sdk\Internetmarke\Model\PageFormat;

/**
 * Catalog endpoint response with page formats and motif galleries.
 *
 * Property names match the API's JSON keys (publicGallery/privateGallery),
 * which differ from the OpenAPI spec (publicCatalog/privateCatalog).
 *
 * @internal
 */
class CatalogResponse
{
    /** @var \DeutschePost\Sdk\Internetmarke\Model\PageFormat[]|null */
    private ?array $pageFormats = null;

    private ?ContractProducts $contractProducts = null;

    private ?PublicCatalog $publicGallery = null;

    private ?PrivateCatalog $privateGallery = null;

    /**
     * @return PageFormat[]
     */
    public function getPageFormats(): array
    {
        return $this->pageFormats ?? [];
    }

    public function getContractProducts(): ?ContractProducts
    {
        return $this->contractProducts;
    }

    public function getPublicCatalog(): ?PublicCatalog
    {
        return $this->publicGallery;
    }

    public function getPrivateCatalog(): ?PrivateCatalog
    {
        return $this->privateGallery;
    }
}
