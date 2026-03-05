<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api\Data;

/**
 * Motif image with description, slogan, and download links.
 *
 * @api
 */
interface ImageItemInterface
{
    public function getImageID(): int;

    public function getImageDescription(): string;

    public function getImageSlogan(): string;

    public function getLinks(): MotiveLinkInterface;
}
