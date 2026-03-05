<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api;

use DeutschePost\Sdk\Internetmarke\Api\Data\CatalogItemInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\ContractProductInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\MotiveLinkInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\PageFormatInterface;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceException;

/**
 * Retrieves page formats, contract products, and motif image catalogs.
 *
 * @api
 */
interface CatalogServiceInterface
{
    /**
     * @return PageFormatInterface[]
     * @throws ServiceException
     */
    public function getPageFormats(): array;

    /**
     * @return ContractProductInterface[]
     * @throws ServiceException
     */
    public function getContractProducts(): array;

    /**
     * @return CatalogItemInterface[]
     * @throws ServiceException
     */
    public function getPublicCatalog(): array;

    /**
     * @return MotiveLinkInterface[]
     * @throws ServiceException
     */
    public function getPrivateCatalog(): array;
}
