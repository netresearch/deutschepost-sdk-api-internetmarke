<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model\RequestType;

/**
 * Label position on a PDF page.
 *
 * @internal
 */
readonly class LabelPosition implements \JsonSerializable
{
    public function __construct(
        private int $page,
        private int $labelX,
        private int $labelY,
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
