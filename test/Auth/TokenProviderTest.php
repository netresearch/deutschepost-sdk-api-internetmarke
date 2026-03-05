<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Auth;

use DeutschePost\Sdk\Internetmarke\Auth\TokenProvider;
use DeutschePost\Sdk\Internetmarke\Model\AuthToken;
use DeutschePost\Sdk\Internetmarke\Api\AuthenticationServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class TokenProviderTest extends TestCase
{
    private function createAuthToken(string $token = 'test-token', int $expiresIn = 3000): AuthToken
    {
        return new AuthToken($token, 100000, $expiresIn, 'EKP-1', 'user@test.de');
    }

    private function createClock(\stdClass $state): ClockInterface
    {
        return new readonly class ($state) implements ClockInterface {
            public function __construct(private \stdClass $state)
            {
            }

            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('@' . $this->state->timestamp);
            }
        };
    }

    #[Test]
    public function returnsTokenFromAuthService(): void
    {
        $authToken = $this->createAuthToken('fresh-token');

        $authService = $this->createMock(AuthenticationServiceInterface::class);
        $authService->expects(self::once())
            ->method('authenticate')
            ->willReturn($authToken);

        $provider = new TokenProvider($authService, 'cid', 'csecret', 'user', 'pass');

        self::assertSame('fresh-token', $provider->getToken());
    }

    #[Test]
    public function cachesTokenAcrossCalls(): void
    {
        $authToken = $this->createAuthToken('cached-token', 3000);

        $authService = $this->createMock(AuthenticationServiceInterface::class);
        $authService->expects(self::once())
            ->method('authenticate')
            ->willReturn($authToken);

        $provider = new TokenProvider($authService, 'cid', 'csecret', 'user', 'pass');

        self::assertSame('cached-token', $provider->getToken());
        self::assertSame('cached-token', $provider->getToken());
    }

    #[Test]
    public function refreshesExpiredToken(): void
    {
        $expiredToken = $this->createAuthToken('expired', 0);
        $freshToken = $this->createAuthToken('refreshed', 3000);

        $authService = $this->createMock(AuthenticationServiceInterface::class);
        $authService->expects(self::exactly(2))
            ->method('authenticate')
            ->willReturnOnConsecutiveCalls($expiredToken, $freshToken);

        $provider = new TokenProvider($authService, 'cid', 'csecret', 'user', 'pass');

        self::assertSame('expired', $provider->getToken());
        self::assertSame('refreshed', $provider->getToken());
    }

    #[Test]
    public function resetTokenForcesReauthentication(): void
    {
        $first = $this->createAuthToken('first-token', 3000);
        $second = $this->createAuthToken('second-token', 3000);

        $authService = $this->createMock(AuthenticationServiceInterface::class);
        $authService->expects(self::exactly(2))
            ->method('authenticate')
            ->willReturnOnConsecutiveCalls($first, $second);

        $provider = new TokenProvider($authService, 'cid', 'csecret', 'user', 'pass');

        self::assertSame('first-token', $provider->getToken());
        $provider->resetToken();
        self::assertSame('second-token', $provider->getToken());
    }

    #[Test]
    public function usesInjectedClockForExpiryCalculation(): void
    {
        $authToken = $this->createAuthToken('cached-token', 3000);

        $authService = $this->createMock(AuthenticationServiceInterface::class);
        $authService->expects(self::once())
            ->method('authenticate')
            ->willReturn($authToken);

        $state = (object) ['timestamp' => 1000000];
        $clock = $this->createClock($state);

        $provider = new TokenProvider($authService, 'cid', 'csecret', 'user', 'pass', $clock);

        // First call authenticates
        self::assertSame('cached-token', $provider->getToken());

        // Advance clock within buffer (3000 - 60 = 2940s lifetime) — token still cached
        $state->timestamp = 1000000 + 2939;
        self::assertSame('cached-token', $provider->getToken());
    }

    #[Test]
    public function refreshesTokenWhenClockPassesExpiry(): void
    {
        $first = $this->createAuthToken('first-token', 3000);
        $second = $this->createAuthToken('second-token', 3000);

        $authService = $this->createMock(AuthenticationServiceInterface::class);
        $authService->expects(self::exactly(2))
            ->method('authenticate')
            ->willReturnOnConsecutiveCalls($first, $second);

        $state = (object) ['timestamp' => 1000000];
        $clock = $this->createClock($state);

        $provider = new TokenProvider($authService, 'cid', 'csecret', 'user', 'pass', $clock);

        // First call authenticates
        self::assertSame('first-token', $provider->getToken());

        // Advance clock past effective expiry (3000 - 60 = 2940s)
        $state->timestamp = 1000000 + 2940;
        self::assertSame('second-token', $provider->getToken());
    }
}
