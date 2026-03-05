<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model\ResponseType;

use DeutschePost\Sdk\Internetmarke\Model\MotiveLink;

/**
 * Private motif image catalog with user-uploaded images.
 *
 * @internal
 */
class PrivateCatalog
{
    /** @var \DeutschePost\Sdk\Internetmarke\Model\MotiveLink[]|null */
    private ?array $imageLink = null;

    /**
     * @return MotiveLink[]
     */
    public function getImageLinks(): array
    {
        return $this->imageLink ?? [];
    }
}
