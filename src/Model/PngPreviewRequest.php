<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model;

use DeutschePost\Sdk\Internetmarke\Api\Dpi;
use DeutschePost\Sdk\Internetmarke\Api\VoucherLayout;

/**
 * Request parameters for previewing a single PNG voucher without charging the wallet.
 *
 * @api
 */
class PngPreviewRequest implements \JsonSerializable
{
    private string $type = 'AppShoppingCartPreviewPNGRequest';

    /**
     * @param VoucherLayout $voucherLayout Voucher layout format.
     * @param int $productCode Product code from CatalogService.
     * @param int|null $imageID Motif image ID from CatalogService.
     * @param Dpi|null $dpi Print resolution.
     * @param bool|null $optimizePNG Optimize PNG rendering.
     */
    public function __construct(
        private readonly VoucherLayout $voucherLayout,
        private readonly int $productCode,
        private readonly ?int $imageID = null,
        private readonly ?Dpi $dpi = null,
        private readonly ?bool $optimizePNG = null,
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
