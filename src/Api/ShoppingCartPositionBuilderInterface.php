<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api;

use DeutschePost\Sdk\Internetmarke\Model\ShoppingCartPosition;

/**
 * Builds shopping cart positions for order requests.
 *
 * Stateful builder that accumulates the total price and remembers the page
 * format ID across multiple create() calls.
 *
 * @api
 */
interface ShoppingCartPositionBuilderInterface
{
    /**
     * Initialize a new builder for the given page format.
     *
     * When columns and rows are provided, create() automatically assigns
     * label positions (page, x, y) to each item, producing PDF positions
     * (AppShoppingCartPDFPosition). Without grid dimensions, callers must
     * call setLabelPosition() manually or positions default to PNG type
     * (AppShoppingCartPosition).
     */
    public static function forPageFormat(int $pageFormatId, int $columns = 0, int $rows = 0): self;

    /**
     * Obtain the page format ID that the builder was initialized with.
     */
    public function getPageFormatId(): int;

    /**
     * Obtain the accumulated total of all created items (euro cents).
     */
    public function getTotalAmount(): int;

    /**
     * Set the product code and price for the current item.
     *
     * Price is not sent to the API per-position — it is a builder-side convenience
     * for accumulating the total across all items.
     *
     * @param int $productCode PPL sales product code
     * @param int $price Price in euro cents
     */
    public function setItemDetails(int $productCode, int $price): self;

    /**
     * Set the voucher layout for the current item.
     *
     * Required per API spec. Determines whether the franking mark is printed
     * in the address zone or franking zone of the label.
     *
     * @param VoucherLayout $layout Voucher layout format.
     */
    public function setVoucherLayout(VoucherLayout $layout): self;

    /**
     * Set sender address for the current item.
     *
     * The REST API requires both sender and receiver if either is provided
     * (AddressBinding schema requires both fields).
     *
     * @param string $country ISO 3166-1 alpha-3 country code
     */
    public function setSenderAddress(
        string $name,
        string $addressLine1,
        string $postalCode,
        string $city,
        string $country,
        ?string $additionalName = null,
        ?string $addressLine2 = null,
    ): self;

    /**
     * Set recipient address for the current item.
     *
     * @param string $country ISO 3166-1 alpha-3 country code
     */
    public function setRecipientAddress(
        string $name,
        string $addressLine1,
        string $postalCode,
        string $city,
        string $country,
        ?string $additionalName = null,
        ?string $addressLine2 = null,
    ): self;

    /**
     * Set motif image ID for the current item.
     *
     * Optional. The image ID refers to a motif from the public or private catalog.
     */
    public function setImageID(int $imageID): self;

    /**
     * Set label position on the page for the current item.
     *
     * Required for PDF format. Specifies where on the page the label is printed.
     */
    public function setLabelPosition(int $page, int $labelX, int $labelY): self;

    /**
     * Create the shopping cart position and reset builder data for the next item.
     * Adds the item price to the running total.
     *
     * The position's positionType discriminator is set automatically:
     * AppShoppingCartPDFPosition if setLabelPosition() was called,
     * AppShoppingCartPosition otherwise.
     */
    public function create(): ShoppingCartPosition;
}
