<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Model;

use DeutschePost\Sdk\Internetmarke\Api\ShoppingCartPositionBuilderInterface;
use DeutschePost\Sdk\Internetmarke\Api\VoucherLayout;
use DeutschePost\Sdk\Internetmarke\Model\ShoppingCartPositionBuilder;
use DeutschePost\Sdk\Internetmarke\Serializer\JsonSerializer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ShoppingCartPositionBuilderTest extends TestCase
{
    /**
     * Serialize a position through the full JsonSerializable + null-filtering chain.
     *
     * @return array<string, mixed>
     */
    private function serialize(\JsonSerializable $position): array
    {
        $serializer = new JsonSerializer();
        return json_decode($serializer->encode($position), true, 512, JSON_THROW_ON_ERROR);
    }

    #[Test]
    public function implementsInterface(): void
    {
        $builder = ShoppingCartPositionBuilder::forPageFormat(1);

        self::assertInstanceOf(ShoppingCartPositionBuilderInterface::class, $builder);
    }

    #[Test]
    public function remembersPageFormatId(): void
    {
        $builder = ShoppingCartPositionBuilder::forPageFormat(42);

        self::assertSame(42, $builder->getPageFormatId());
    }

    #[Test]
    public function totalStartsAtZero(): void
    {
        $builder = ShoppingCartPositionBuilder::forPageFormat(1);

        self::assertSame(0, $builder->getTotalAmount());
    }

    #[Test]
    public function accumulatesTotalAcrossMultipleCreates(): void
    {
        $builder = ShoppingCartPositionBuilder::forPageFormat(1);

        $builder->setItemDetails(10001, 85)
            ->setVoucherLayout(VoucherLayout::AddressZone)
            ->create();

        $builder->setItemDetails(10002, 160)
            ->setVoucherLayout(VoucherLayout::AddressZone)
            ->create();

        self::assertSame(245, $builder->getTotalAmount());
    }

    #[Test]
    public function createResetsItemDataForNextPosition(): void
    {
        $builder = ShoppingCartPositionBuilder::forPageFormat(1);

        $builder->setItemDetails(10001, 85)
            ->setVoucherLayout(VoucherLayout::AddressZone)
            ->setSenderAddress('Sender', 'Street 1', '12345', 'Berlin', 'DEU')
            ->setRecipientAddress('Receiver', 'Street 2', '54321', 'Munich', 'DEU')
            ->setImageID(99)
            ->setLabelPosition(1, 1, 1)
            ->create();

        // Second position with minimal data — should not carry over from first
        $builder->setItemDetails(10002, 160)
            ->setVoucherLayout(VoucherLayout::FrankingZone);

        $position = $builder->create();
        $data = $this->serialize($position);

        self::assertSame(10002, $data['productCode']);
        self::assertSame('FRANKING_ZONE', $data['voucherLayout']);
        self::assertArrayNotHasKey('address', $data);
        self::assertArrayNotHasKey('imageID', $data);
        self::assertArrayNotHasKey('position', $data);
        self::assertSame('AppShoppingCartPosition', $data['positionType']);
    }

    #[Test]
    public function createsPositionWithAddresses(): void
    {
        $builder = ShoppingCartPositionBuilder::forPageFormat(1);

        $position = $builder
            ->setItemDetails(10001, 85)
            ->setVoucherLayout(VoucherLayout::AddressZone)
            ->setSenderAddress('Max Mustermann', 'Sträßchensweg 10', '53113', 'Bonn', 'DEU')
            ->setRecipientAddress(
                'Erika Mustermann',
                'Heidestraße 17',
                '51147',
                'Köln',
                'DEU',
                'c/o Schmidt',
                'Apartment 3',
            )
            ->create();

        $data = $this->serialize($position);

        self::assertSame(10001, $data['productCode']);
        self::assertSame('ADDRESS_ZONE', $data['voucherLayout']);

        self::assertSame('Max Mustermann', $data['address']['sender']['name']);
        self::assertSame('Sträßchensweg 10', $data['address']['sender']['addressLine1']);
        self::assertSame('53113', $data['address']['sender']['postalCode']);
        self::assertSame('Bonn', $data['address']['sender']['city']);
        self::assertSame('DEU', $data['address']['sender']['country']);

        self::assertSame('Erika Mustermann', $data['address']['receiver']['name']);
        self::assertSame('Heidestraße 17', $data['address']['receiver']['addressLine1']);
        self::assertSame('51147', $data['address']['receiver']['postalCode']);
        self::assertSame('Köln', $data['address']['receiver']['city']);
        self::assertSame('DEU', $data['address']['receiver']['country']);
        self::assertSame('c/o Schmidt', $data['address']['receiver']['additionalName']);
        self::assertSame('Apartment 3', $data['address']['receiver']['addressLine2']);
    }

    #[Test]
    public function createsPositionWithImageId(): void
    {
        $builder = ShoppingCartPositionBuilder::forPageFormat(1);

        $position = $builder
            ->setItemDetails(10001, 85)
            ->setVoucherLayout(VoucherLayout::FrankingZone)
            ->setImageID(42)
            ->create();

        $data = $this->serialize($position);

        self::assertSame(42, $data['imageID']);
    }

    #[Test]
    public function createsPositionWithoutPosition(): void
    {
        $builder = ShoppingCartPositionBuilder::forPageFormat(1);

        $position = $builder
            ->setItemDetails(10001, 85)
            ->setVoucherLayout(VoucherLayout::AddressZone)
            ->create();

        $data = $this->serialize($position);

        self::assertArrayNotHasKey('position', $data);
        self::assertSame('AppShoppingCartPosition', $data['positionType']);
    }

    #[Test]
    public function createsPdfPositionWithLabelPosition(): void
    {
        $builder = ShoppingCartPositionBuilder::forPageFormat(1);

        $position = $builder
            ->setItemDetails(10001, 85)
            ->setVoucherLayout(VoucherLayout::AddressZone)
            ->setLabelPosition(1, 2, 3)
            ->create();

        $data = $this->serialize($position);

        self::assertSame(['page' => 1, 'labelX' => 2, 'labelY' => 3], $data['position']);
        self::assertSame('AppShoppingCartPDFPosition', $data['positionType']);
    }

    #[Test]
    public function autoCalculatesLabelPositionFromGridDimensions(): void
    {
        $builder = ShoppingCartPositionBuilder::forPageFormat(1, 2, 3);

        $position = $builder
            ->setItemDetails(10001, 85)
            ->setVoucherLayout(VoucherLayout::AddressZone)
            ->create();

        $data = $this->serialize($position);

        self::assertSame(['page' => 1, 'labelX' => 1, 'labelY' => 1], $data['position']);
        self::assertSame('AppShoppingCartPDFPosition', $data['positionType']);
    }

    #[Test]
    public function autoPositionPaginatesAcrossGrid(): void
    {
        // 2 columns × 2 rows = 4 labels per page
        $builder = ShoppingCartPositionBuilder::forPageFormat(1, 2, 2);
        $positions = [];

        for ($i = 0; $i < 5; $i++) {
            $positions[] = $this->serialize(
                $builder->setItemDetails(10001, 85)
                    ->setVoucherLayout(VoucherLayout::FrankingZone)
                    ->create()
            );
        }

        // Page 1: (1,1), (2,1), (1,2), (2,2)
        self::assertSame(['page' => 1, 'labelX' => 1, 'labelY' => 1], $positions[0]['position']);
        self::assertSame(['page' => 1, 'labelX' => 2, 'labelY' => 1], $positions[1]['position']);
        self::assertSame(['page' => 1, 'labelX' => 1, 'labelY' => 2], $positions[2]['position']);
        self::assertSame(['page' => 1, 'labelX' => 2, 'labelY' => 2], $positions[3]['position']);
        // Page 2: overflow
        self::assertSame(['page' => 2, 'labelX' => 1, 'labelY' => 1], $positions[4]['position']);
    }

    #[Test]
    public function manualLabelPositionOverridesAutoCalculation(): void
    {
        $builder = ShoppingCartPositionBuilder::forPageFormat(1, 2, 5);

        $position = $builder
            ->setItemDetails(10001, 85)
            ->setVoucherLayout(VoucherLayout::AddressZone)
            ->setLabelPosition(3, 2, 4)
            ->create();

        $data = $this->serialize($position);

        self::assertSame(['page' => 3, 'labelX' => 2, 'labelY' => 4], $data['position']);
        self::assertSame('AppShoppingCartPDFPosition', $data['positionType']);
    }

    #[Test]
    public function throwsWhenCreatingWithoutItemDetails(): void
    {
        $builder = ShoppingCartPositionBuilder::forPageFormat(1);

        $builder->setVoucherLayout(VoucherLayout::AddressZone);

        $this->expectException(\LogicException::class);

        $builder->create();
    }

    #[Test]
    public function throwsWhenCreatingWithoutVoucherLayout(): void
    {
        $builder = ShoppingCartPositionBuilder::forPageFormat(1);

        $builder->setItemDetails(10001, 85);

        $this->expectException(\LogicException::class);

        $builder->create();
    }

    #[Test]
    public function throwsWhenOnlySenderAddressIsSet(): void
    {
        $builder = ShoppingCartPositionBuilder::forPageFormat(1);

        $builder->setItemDetails(10001, 85)
            ->setVoucherLayout(VoucherLayout::AddressZone)
            ->setSenderAddress('Max', 'Street 1', '12345', 'Berlin', 'DEU');

        $this->expectException(\LogicException::class);

        $builder->create();
    }

    #[Test]
    public function throwsWhenOnlyRecipientAddressIsSet(): void
    {
        $builder = ShoppingCartPositionBuilder::forPageFormat(1);

        $builder->setItemDetails(10001, 85)
            ->setVoucherLayout(VoucherLayout::AddressZone)
            ->setRecipientAddress('Erika', 'Street 2', '54321', 'Munich', 'DEU');

        $this->expectException(\LogicException::class);

        $builder->create();
    }

    #[Test]
    public function fluentChainingWorks(): void
    {
        $builder = ShoppingCartPositionBuilder::forPageFormat(1);

        $result = $builder
            ->setItemDetails(10001, 85)
            ->setVoucherLayout(VoucherLayout::AddressZone)
            ->setSenderAddress('S', 'A1', '12345', 'City', 'DEU')
            ->setRecipientAddress('R', 'A2', '54321', 'Town', 'DEU')
            ->setImageID(1)
            ->setLabelPosition(1, 1, 1);

        self::assertSame($builder, $result);
    }

    #[Test]
    public function acceptsVoucherLayoutEnum(): void
    {
        $builder = ShoppingCartPositionBuilder::forPageFormat(1);

        $position = $builder
            ->setItemDetails(10001, 85)
            ->setVoucherLayout(VoucherLayout::AddressZone)
            ->create();

        $data = $this->serialize($position);

        self::assertSame('ADDRESS_ZONE', $data['voucherLayout']);
    }

    #[Test]
    public function acceptsVoucherLayoutFrankingZoneEnum(): void
    {
        $builder = ShoppingCartPositionBuilder::forPageFormat(1);

        $position = $builder
            ->setItemDetails(10001, 85)
            ->setVoucherLayout(VoucherLayout::FrankingZone)
            ->create();

        $data = $this->serialize($position);

        self::assertSame('FRANKING_ZONE', $data['voucherLayout']);
    }
}
