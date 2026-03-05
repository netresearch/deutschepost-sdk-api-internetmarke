<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model\RequestType;

/**
 * Sender or receiver address for a shopping cart position.
 *
 * @internal
 */
readonly class Address implements \JsonSerializable
{
    public function __construct(
        private string $name,
        private string $addressLine1,
        private string $postalCode,
        private string $city,
        private string $country,
        private ?string $additionalName = null,
        private ?string $addressLine2 = null,
    ) {
    }

    /**
     * @return array<string, string|null>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
