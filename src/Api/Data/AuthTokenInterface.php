<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api\Data;

/**
 * Authentication response containing Bearer token and account metadata.
 *
 * @api
 */
interface AuthTokenInterface
{
    public function getAccessToken(): string;

    /**
     * Portokasse wallet balance in cents.
     */
    public function getWalletBalance(): int;

    /**
     * Token lifetime in seconds. Read from response, not hardcoded.
     */
    public function getExpiresIn(): int;

    /**
     * Customer EKP number.
     */
    public function getExternalCustomerId(): string;

    public function getAuthenticatedUser(): string;

    /**
     * Token type, e.g. "BearerToken".
     */
    public function getTokenType(): string;

    /**
     * Timestamp when the token was issued.
     */
    public function getIssuedAt(): string;

    /**
     * Optional informational message from the API.
     */
    public function getInfoMessage(): string;
}
