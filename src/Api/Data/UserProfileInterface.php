<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api\Data;

/**
 * User profile with account and address information.
 *
 * @api
 */
interface UserProfileInterface
{
    public function getEkp(): string;

    public function getCompany(): string;

    public function getSalutation(): string;

    public function getTitle(): string;

    /**
     * Invoice type: NO, PAPER, ONLINE, or EPOST.
     */
    public function getInvoiceType(): string;

    /**
     * Invoice frequency: DECADE or DAILY.
     */
    public function getInvoiceFrequency(): string;

    public function getMail(): string;

    public function getFirstname(): string;

    public function getLastname(): string;

    public function getStreet(): string;

    public function getHouseNo(): string;

    public function getZip(): string;

    public function getCity(): string;

    public function getCountry(): string;

    public function getPhone(): string;

    public function getPobox(): string;

    public function getPoboxZip(): string;

    public function getPoboxCity(): string;

    public function getEpostbrief(): string;
}
