<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model;

use DeutschePost\Sdk\Internetmarke\Api\Data\AuthTokenInterface;

/**
 * Authentication response containing Bearer token and account metadata.
 *
 * @api
 */
readonly class AuthToken implements AuthTokenInterface
{
    public function __construct(
        private string $accessToken,
        private int $walletBalance,
        private int $expiresIn,
        private string $externalCustomerId,
        private string $authenticatedUser,
        private string $tokenType = '',
        private string $issuedAt = '',
        private string $infoMessage = '',
    ) {
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * Portokasse wallet balance in cents.
     */
    public function getWalletBalance(): int
    {
        return $this->walletBalance;
    }

    /**
     * Token lifetime in seconds. Read from response, not hardcoded.
     */
    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }

    /**
     * Customer EKP number.
     */
    public function getExternalCustomerId(): string
    {
        return $this->externalCustomerId;
    }

    public function getAuthenticatedUser(): string
    {
        return $this->authenticatedUser;
    }

    /**
     * Token type, e.g. "BearerToken".
     */
    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    /**
     * Timestamp when the token was issued.
     */
    public function getIssuedAt(): string
    {
        return $this->issuedAt;
    }

    /**
     * Optional informational message from the API.
     */
    public function getInfoMessage(): string
    {
        return $this->infoMessage;
    }
}
