<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model;

use DeutschePost\Sdk\Internetmarke\Api\Dpi;
use DeutschePost\Sdk\Internetmarke\Api\ShippingList;

/**
 * Request parameters for creating a voucher order.
 *
 * @api
 */
class OrderRequest implements \JsonSerializable
{
    private string $type = 'AppShoppingCartPDFRequest';

    /**
     * @param ShoppingCartPosition[] $positions
     * @param int $total Total price in cents.
     * @param int $pageFormatId Page format ID from CatalogService.
     * @param bool $createManifest Generate posting receipt.
     * @param ShippingList $createShippingList Shipping list type.
     * @param Dpi $dpi Print resolution.
     * @param string|null $shopOrderId Cart ID from initializeCart (max 18 chars).
     */
    public function __construct(
        private readonly array $positions,
        private readonly int $total,
        private readonly int $pageFormatId,
        private readonly bool $createManifest = false,
        private readonly ShippingList $createShippingList = ShippingList::None,
        private readonly Dpi $dpi = Dpi::Dpi300,
        private readonly ?string $shopOrderId = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
