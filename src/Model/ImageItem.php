<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model;

use DeutschePost\Sdk\Internetmarke\Api\Data\ImageItemInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\MotiveLinkInterface;

/**
 * Motif image with description, slogan, and download links.
 *
 * Not declared readonly: mutable properties provide safe defaults for optional API fields that JsonMapper may not populate.
 * Treat as immutable — the public API exposes only getters.
 *
 * @api
 */
class ImageItem implements ImageItemInterface
{
    private ?int $imageID = null;
    private ?string $imageDescription = null;
    private ?string $imageSlogan = null;
    private ?MotiveLink $links = null;

    public function getImageID(): int
    {
        return $this->imageID ?? 0;
    }

    public function getImageDescription(): string
    {
        return $this->imageDescription ?? '';
    }

    public function getImageSlogan(): string
    {
        return $this->imageSlogan ?? '';
    }

    public function getLinks(): MotiveLinkInterface
    {
        return $this->links ?? new MotiveLink();
    }
}
