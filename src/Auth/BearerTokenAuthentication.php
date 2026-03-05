<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Auth;

use Http\Message\Authentication;
use Psr\Http\Message\RequestInterface;

/**
 * Adds Bearer token authorization using a TokenProvider for lazy token acquisition.
 *
 * Unlike the static php-http Bearer implementation, this calls
 * TokenProvider::getToken() on each request to support automatic
 * token refresh.
 *
 * @internal
 */
readonly class BearerTokenAuthentication implements Authentication
{
    public function __construct(private TokenProvider $tokenProvider)
    {
    }

    public function authenticate(RequestInterface $request): RequestInterface
    {
        return $request->withHeader('Authorization', 'Bearer ' . $this->tokenProvider->getToken());
    }
}
