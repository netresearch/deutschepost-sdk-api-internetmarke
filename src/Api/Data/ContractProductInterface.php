<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api\Data;

/**
 * Contract-specific product with product code and price.
 *
 * @api
 */
interface ContractProductInterface
{
    public function getProductCode(): int;

    /**
     * Price in euro cents.
     */
    public function getPrice(): int;
}
