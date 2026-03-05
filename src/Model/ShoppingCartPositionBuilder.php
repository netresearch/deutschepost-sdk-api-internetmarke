<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model;

use DeutschePost\Sdk\Internetmarke\Api\ShoppingCartPositionBuilderInterface;
use DeutschePost\Sdk\Internetmarke\Api\VoucherLayout;
use DeutschePost\Sdk\Internetmarke\Model\RequestType\Address;
use DeutschePost\Sdk\Internetmarke\Model\RequestType\AddressBinding;
use DeutschePost\Sdk\Internetmarke\Model\RequestType\LabelPosition;

/**
 * Builds shopping cart positions for order requests.
 *
 * @api
 */
class ShoppingCartPositionBuilder implements ShoppingCartPositionBuilderInterface
{
    private int $totalAmount = 0;
    private int $positionCounter = 0;

    private int $productCode = 0;
    private int $price = 0;
    private string $voucherLayout = '';
    private ?Address $senderAddress = null;
    private ?Address $recipientAddress = null;
    private ?int $imageID = null;
    private ?LabelPosition $position = null;

    private function __construct(
        private readonly int $pageFormatId,
        private readonly int $columns = 0,
        private readonly int $rows = 0,
    ) {
    }

    public static function forPageFormat(int $pageFormatId, int $columns = 0, int $rows = 0): self
    {
        return new self($pageFormatId, $columns, $rows);
    }

    public function getPageFormatId(): int
    {
        return $this->pageFormatId;
    }

    public function getTotalAmount(): int
    {
        return $this->totalAmount;
    }

    public function setItemDetails(int $productCode, int $price): self
    {
        $this->productCode = $productCode;
        $this->price = $price;

        return $this;
    }

    public function setVoucherLayout(VoucherLayout $layout): self
    {
        $this->voucherLayout = $layout->value;

        return $this;
    }

    public function setSenderAddress(
        string $name,
        string $addressLine1,
        string $postalCode,
        string $city,
        string $country,
        ?string $additionalName = null,
        ?string $addressLine2 = null,
    ): self {
        $this->senderAddress = new Address(
            $name,
            $addressLine1,
            $postalCode,
            $city,
            $country,
            $additionalName,
            $addressLine2,
        );

        return $this;
    }

    public function setRecipientAddress(
        string $name,
        string $addressLine1,
        string $postalCode,
        string $city,
        string $country,
        ?string $additionalName = null,
        ?string $addressLine2 = null,
    ): self {
        $this->recipientAddress = new Address(
            $name,
            $addressLine1,
            $postalCode,
            $city,
            $country,
            $additionalName,
            $addressLine2,
        );

        return $this;
    }

    public function setImageID(int $imageID): self
    {
        $this->imageID = $imageID;

        return $this;
    }

    public function setLabelPosition(int $page, int $labelX, int $labelY): self
    {
        $this->position = new LabelPosition($page, $labelX, $labelY);

        return $this;
    }

    public function create(): ShoppingCartPosition
    {
        if ($this->productCode === 0) {
            throw new \LogicException('Product code is required. Call setItemDetails() before create().');
        }

        if ($this->voucherLayout === '') {
            throw new \LogicException('Voucher layout is required. Call setVoucherLayout() before create().');
        }

        $hasSender = $this->senderAddress instanceof Address;
        $hasRecipient = $this->recipientAddress instanceof Address;
        if ($hasSender !== $hasRecipient) {
            throw new \LogicException('Both sender and recipient address are required. Set both or neither.');
        }

        if ($this->columns > 0 && $this->rows > 0 && !$this->position instanceof LabelPosition) {
            $labelsPerPage = $this->columns * $this->rows;
            $page = (int) floor($this->positionCounter / $labelsPerPage) + 1;
            $positionInPage = $this->positionCounter % $labelsPerPage;
            $labelX = ($positionInPage % $this->columns) + 1;
            $labelY = (int) floor($positionInPage / $this->columns) + 1;
            $this->position = new LabelPosition($page, $labelX, $labelY);
        }

        $address = ($this->senderAddress instanceof Address && $this->recipientAddress instanceof Address)
            ? new AddressBinding($this->senderAddress, $this->recipientAddress)
            : null;

        $positionType = $this->position instanceof LabelPosition
            ? 'AppShoppingCartPDFPosition'
            : 'AppShoppingCartPosition';

        $position = new ShoppingCartPosition(
            $this->productCode,
            $this->voucherLayout,
            $address,
            $this->imageID,
            $this->position,
            $positionType,
        );

        $this->totalAmount += $this->price;
        $this->positionCounter++;
        $this->resetItemData();

        return $position;
    }

    private function resetItemData(): void
    {
        $this->productCode = 0;
        $this->price = 0;
        $this->voucherLayout = '';
        $this->senderAddress = null;
        $this->recipientAddress = null;
        $this->imageID = null;
        $this->position = null;
    }
}
