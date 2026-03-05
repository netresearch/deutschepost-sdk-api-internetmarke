<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model;

use DeutschePost\Sdk\Internetmarke\Api\Data\RefundInterface;

/**
 * Refund response with retoure identifiers.
 *
 * Not declared readonly: mutable properties provide safe defaults for optional API fields that JsonMapper may not populate.
 * Treat as immutable — the public API exposes only getters.
 *
 * @api
 */
class Refund implements RefundInterface
{
    private ?string $shopRetoureId = null;

    /** @var string|null API schema types this as string (unlike RetoureState which types it as integer). */
    private ?string $retoureTransactionId = null;

    public function getShopRetoureId(): string
    {
        return $this->shopRetoureId ?? '';
    }

    public function getRetoureTransactionId(): string
    {
        return $this->retoureTransactionId ?? '';
    }
}
