<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api;

use DeutschePost\Sdk\Internetmarke\Api\Data\AuthTokenInterface;

/**
 * Acquires Bearer tokens for API authentication.
 *
 * @internal
 */
interface AuthenticationServiceInterface
{
    /**
     * @throws \DeutschePost\Sdk\Internetmarke\Exception\AuthenticationException On invalid credentials (401).
     * @throws \DeutschePost\Sdk\Internetmarke\Exception\ServiceException On other API errors.
     */
    public function authenticate(
        string $clientId,
        string $clientSecret,
        string $username,
        string $password,
    ): AuthTokenInterface;
}
