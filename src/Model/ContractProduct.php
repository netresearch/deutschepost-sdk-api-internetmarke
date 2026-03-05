<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model;

use DeutschePost\Sdk\Internetmarke\Api\Data\ContractProductInterface;

/**
 * Contract-specific product with product code and price.
 *
 * Not declared readonly: mutable properties provide safe defaults for optional API fields that JsonMapper may not populate.
 * Treat as immutable — the public API exposes only getters.
 *
 * @api
 */
class ContractProduct implements ContractProductInterface
{
    private ?int $productCode = null;
    private ?int $price = null;

    public function getProductCode(): int
    {
        return $this->productCode ?? 0;
    }

    /**
     * Price in euro cents.
     */
    public function getPrice(): int
    {
        return $this->price ?? 0;
    }
}
