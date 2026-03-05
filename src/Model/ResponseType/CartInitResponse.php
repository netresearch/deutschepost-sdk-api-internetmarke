<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model\ResponseType;

/**
 * Response from the cart initialization endpoint.
 *
 * @internal
 */
class CartInitResponse
{
    private ?string $shopOrderId = null;

    public function getShopOrderId(): string
    {
        return $this->shopOrderId ?? '';
    }
}
