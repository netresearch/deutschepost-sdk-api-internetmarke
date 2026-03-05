<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model\ResponseType;

use DeutschePost\Sdk\Internetmarke\Model\ContractProduct;

/**
 * Wrapper for the contract products collection in the catalog response.
 *
 * @internal
 */
class ContractProducts
{
    /** @var \DeutschePost\Sdk\Internetmarke\Model\ContractProduct[]|null */
    private ?array $products = null;

    /**
     * @return ContractProduct[]
     */
    public function getProducts(): array
    {
        return $this->products ?? [];
    }
}
