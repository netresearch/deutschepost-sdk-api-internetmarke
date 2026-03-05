<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model;

use DeutschePost\Sdk\Internetmarke\Api\Data\PageFormatInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\PageLayoutInterface;
use DeutschePost\Sdk\Internetmarke\Model\ResponseType\PageLayout;

/**
 * Page format for label printing (e.g. DIN A4 normal paper, labels, envelopes).
 *
 * Not declared readonly: mutable properties provide safe defaults for optional API fields that JsonMapper may not populate.
 * Treat as immutable — the public API exposes only getters.
 *
 * @api
 */
class PageFormat implements PageFormatInterface
{
    private ?int $id = null;
    private ?string $name = null;
    private ?string $description = null;
    private ?bool $isAddressPossible = null;
    private ?bool $isImagePossible = null;
    private ?string $pageType = null;
    private ?PageLayout $pageLayout = null;

    public function getId(): int
    {
        return $this->id ?? 0;
    }

    public function getName(): string
    {
        return $this->name ?? '';
    }

    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    public function isAddressPossible(): bool
    {
        return $this->isAddressPossible ?? false;
    }

    public function isImagePossible(): bool
    {
        return $this->isImagePossible ?? false;
    }

    public function getPageType(): string
    {
        return $this->pageType ?? '';
    }

    public function getPageLayout(): PageLayoutInterface
    {
        return $this->pageLayout ?? new PageLayout();
    }
}
