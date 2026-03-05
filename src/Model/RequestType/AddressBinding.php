<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model\RequestType;

/**
 * Sender-receiver address pair for a shopping cart position.
 *
 * @internal
 */
readonly class AddressBinding implements \JsonSerializable
{
    public function __construct(
        private Address $sender,
        private Address $receiver,
    ) {
    }

    /**
     * @return array<string, Address>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
