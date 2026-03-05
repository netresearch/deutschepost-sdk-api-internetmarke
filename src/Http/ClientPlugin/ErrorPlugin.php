<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Http\ClientPlugin;

use DeutschePost\Sdk\Internetmarke\Exception\AuthenticationErrorHttpException;
use DeutschePost\Sdk\Internetmarke\Exception\DetailedErrorHttpException;
use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Converts HTTP error responses into typed HTTP exceptions.
 *
 * 401/403 responses throw AuthenticationErrorHttpException.
 * All other 4xx/5xx responses throw DetailedErrorHttpException.
 * Responses below 400 (including 3xx redirects) pass through unchanged.
 * Error messages are extracted from JSON response bodies when available.
 *
 * @internal
 */
final class ErrorPlugin implements Plugin
{
    /**
     * @param callable(RequestInterface): Promise $next
     * @param callable(RequestInterface): Promise $first
     */
    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        return $next($request)->then(
            function (ResponseInterface $response) use ($request): ResponseInterface {
                $statusCode = $response->getStatusCode();

                if ($statusCode < 400) {
                    return $response;
                }

                $message = $this->extractErrorMessage($response);

                if ($statusCode === 401 || $statusCode === 403) {
                    throw new AuthenticationErrorHttpException($message, $request, $response);
                }

                throw new DetailedErrorHttpException($message, $request, $response);
            }
        );
    }

    private function extractErrorMessage(ResponseInterface $response): string
    {
        $body = (string) $response->getBody();
        $response->getBody()->rewind();
        $data = json_decode($body, true);

        if (!is_array($data)) {
            return $response->getReasonPhrase();
        }

        $parts = array_filter([
            $data['title'] ?? null,
            $data['detail'] ?? null,
        ]);

        return implode(': ', $parts) ?: $response->getReasonPhrase();
    }
}
