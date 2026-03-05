<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model;

use DeutschePost\Sdk\Internetmarke\Api\Data\MotiveLinkInterface;

/**
 * Motif image link with thumbnail.
 *
 * Not declared readonly: mutable properties provide safe defaults for optional API fields that JsonMapper may not populate.
 * Treat as immutable — the public API exposes only getters.
 *
 * @api
 */
class MotiveLink implements MotiveLinkInterface
{
    private ?string $link = null;
    private ?string $linkThumbnail = null;

    public function getLink(): string
    {
        return $this->link ?? '';
    }

    public function getLinkThumbnail(): string
    {
        return $this->linkThumbnail ?? '';
    }
}
