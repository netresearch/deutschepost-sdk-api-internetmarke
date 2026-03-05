<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model\ResponseType;

/**
 * Response from the directCheckout PDF endpoint.
 *
 * Note: walletBallance is intentionally misspelled to match the API response.
 *
 * @internal
 */
class CheckoutResponse
{
    private ?string $type = null;

    private ?string $link = null;

    private ?string $manifestLink = null;

    private ?ShoppingCart $shoppingCart = null;

    private ?int $walletBallance = null;

    public function getType(): string
    {
        return $this->type ?? '';
    }

    public function getLink(): string
    {
        return $this->link ?? '';
    }

    public function getManifestLink(): ?string
    {
        return $this->manifestLink;
    }

    public function getShoppingCart(): ?ShoppingCart
    {
        return $this->shoppingCart;
    }

    public function getWalletBallance(): int
    {
        return $this->walletBallance ?? 0;
    }
}
