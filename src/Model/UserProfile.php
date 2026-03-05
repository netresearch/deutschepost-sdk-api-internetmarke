<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model;

use DeutschePost\Sdk\Internetmarke\Api\Data\UserProfileInterface;

/**
 * User profile with account and address information.
 *
 * Not declared readonly: mutable properties provide safe defaults for optional API fields that JsonMapper may not populate.
 * Treat as immutable — the public API exposes only getters.
 *
 * @api
 */
class UserProfile implements UserProfileInterface
{
    private ?string $ekp = null;
    private ?string $company = null;
    private ?string $salutation = null;
    private ?string $title = null;
    private ?string $invoiceType = null;
    private ?string $invoiceFrequency = null;
    private ?string $mail = null;
    private ?string $firstname = null;
    private ?string $lastname = null;
    private ?string $street = null;
    private ?string $houseNo = null;
    private ?string $zip = null;
    private ?string $city = null;
    private ?string $country = null;
    private ?string $phone = null;
    private ?string $pobox = null;
    private ?string $poboxZip = null;
    private ?string $poboxCity = null;
    private ?string $epostbrief = null;

    public function getEkp(): string
    {
        return $this->ekp ?? '';
    }

    public function getCompany(): string
    {
        return $this->company ?? '';
    }

    public function getSalutation(): string
    {
        return $this->salutation ?? '';
    }

    public function getTitle(): string
    {
        return $this->title ?? '';
    }

    public function getInvoiceType(): string
    {
        return $this->invoiceType ?? '';
    }

    public function getInvoiceFrequency(): string
    {
        return $this->invoiceFrequency ?? '';
    }

    public function getMail(): string
    {
        return $this->mail ?? '';
    }

    public function getFirstname(): string
    {
        return $this->firstname ?? '';
    }

    public function getLastname(): string
    {
        return $this->lastname ?? '';
    }

    public function getStreet(): string
    {
        return $this->street ?? '';
    }

    public function getHouseNo(): string
    {
        return $this->houseNo ?? '';
    }

    public function getZip(): string
    {
        return $this->zip ?? '';
    }

    public function getCity(): string
    {
        return $this->city ?? '';
    }

    public function getCountry(): string
    {
        return $this->country ?? '';
    }

    public function getPhone(): string
    {
        return $this->phone ?? '';
    }

    public function getPobox(): string
    {
        return $this->pobox ?? '';
    }

    public function getPoboxZip(): string
    {
        return $this->poboxZip ?? '';
    }

    public function getPoboxCity(): string
    {
        return $this->poboxCity ?? '';
    }

    public function getEpostbrief(): string
    {
        return $this->epostbrief ?? '';
    }
}
