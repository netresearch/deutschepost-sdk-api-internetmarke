<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api\Data;

/**
 * Motif image link with thumbnail.
 *
 * @api
 */
interface MotiveLinkInterface
{
    public function getLink(): string;

    public function getLinkThumbnail(): string;
}
