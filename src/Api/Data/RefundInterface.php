<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api\Data;

/**
 * Refund response with retoure identifiers.
 *
 * @api
 */
interface RefundInterface
{
    public function getShopRetoureId(): string;

    public function getRetoureTransactionId(): string;
}
