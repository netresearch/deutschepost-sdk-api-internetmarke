<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api\Data;

use DeutschePost\Sdk\Internetmarke\Api\Data\PageLayoutInterface;

/**
 * Page format for label printing (e.g. DIN A4 normal paper, labels, envelopes).
 *
 * @api
 */
interface PageFormatInterface
{
    public const string PAGE_TYPE_REGULAR_PAGE = 'REGULARPAGE';
    public const string PAGE_TYPE_ENVELOPE = 'ENVELOPE';
    public const string PAGE_TYPE_LABEL_PRINTER = 'LABELPRINTER';
    public const string PAGE_TYPE_LABEL_PAGE = 'LABELPAGE';

    public const string ORIENTATION_LANDSCAPE = 'LANDSCAPE';
    public const string ORIENTATION_PORTRAIT = 'PORTRAIT';

    public function getId(): int;

    public function getName(): string;

    public function getDescription(): string;

    public function isAddressPossible(): bool;

    public function isImagePossible(): bool;

    /**
     * @return string One of the PAGE_TYPE_* constants.
     */
    public function getPageType(): string;

    /**
     * Page layout with orientation, dimensions, label grid, and margins.
     */
    public function getPageLayout(): PageLayoutInterface;
}
