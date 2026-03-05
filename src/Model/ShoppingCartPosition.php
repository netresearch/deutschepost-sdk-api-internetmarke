<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model;

use DeutschePost\Sdk\Internetmarke\Model\RequestType\AddressBinding;
use DeutschePost\Sdk\Internetmarke\Model\RequestType\LabelPosition;

/**
 * A single item in a shopping cart order.
 *
 * Supports both PNG positions (AppShoppingCartPosition) and PDF positions
 * (AppShoppingCartPDFPosition) via the positionType discriminator.
 *
 * @api
 */
readonly class ShoppingCartPosition implements \JsonSerializable
{
    /**
     * @param int $productCode PPL sales product code.
     * @param string $voucherLayout ADDRESS_ZONE or FRANKING_ZONE.
     * @param AddressBinding|null $address Sender-receiver address pair.
     * @param int|null $imageID Motif image ID (API name: imageID).
     * @param LabelPosition|null $position Label position (PDF only).
     * @param string $positionType Position type discriminator.
     */
    public function __construct(
        private int $productCode,
        private string $voucherLayout,
        private ?AddressBinding $address = null,
        private ?int $imageID = null,
        private ?LabelPosition $position = null,
        private string $positionType = 'AppShoppingCartPosition',
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
