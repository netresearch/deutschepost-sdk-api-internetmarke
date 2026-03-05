<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Auth;

use DeutschePost\Sdk\Internetmarke\Api\Data\AuthTokenInterface;
use DeutschePost\Sdk\Internetmarke\Api\AuthenticationServiceInterface;
use Psr\Clock\ClockInterface;

/**
 * Manages token lifecycle with caching and automatic refresh.
 *
 * Tokens are refreshed when expired (based on expires_in from response)
 * with a 60-second buffer before actual expiry.
 *
 * @internal
 */
class TokenProvider
{
    private const int REFRESH_BUFFER_SECONDS = 60;

    private ?AuthTokenInterface $token = null;

    private ?int $expiresAt = null;

    public function __construct(
        private readonly AuthenticationServiceInterface $authService,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $username,
        private readonly string $password,
        private readonly ?ClockInterface $clock = null,
    ) {
    }

    /**
     * Returns a valid Bearer token, refreshing if needed.
     */
    public function getToken(): string
    {
        if (!$this->token instanceof AuthTokenInterface || $this->isExpired()) {
            $this->token = $this->authService->authenticate(
                $this->clientId,
                $this->clientSecret,
                $this->username,
                $this->password,
            );

            $this->expiresAt = $this->now() + max($this->token->getExpiresIn() - self::REFRESH_BUFFER_SECONDS, 0);
        }

        return $this->token->getAccessToken();
    }

    /**
     * Forces the next getToken() call to re-authenticate.
     */
    public function resetToken(): void
    {
        $this->token = null;
        $this->expiresAt = null;
    }

    private function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->now() >= $this->expiresAt;
    }

    private function now(): int
    {
        return $this->clock?->now()->getTimestamp() ?? time();
    }
}
