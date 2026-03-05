<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Exception;

/**
 * Wraps arbitrary throwables into SDK exceptions.
 *
 * @internal
 */
class ServiceExceptionFactory
{
    /**
     * Idempotent exception converter for service catch-all blocks.
     *
     * Returns ServiceException instances unchanged (including subtypes
     * like AuthenticationErrorHttpException). Wraps everything else
     * in a generic ServiceException.
     */
    public static function create(\Throwable $exception): ServiceException
    {
        return $exception instanceof ServiceException
            ? $exception
            : new ServiceException($exception->getMessage(), $exception->getCode(), $exception);
    }

    /**
     * Always wraps in a new ServiceException, even if already a ServiceException subtype.
     *
     * Unlike create(), which returns ServiceException instances unchanged,
     * this method always creates a fresh wrapping ServiceException.
     */
    public static function wrapInServiceException(\Throwable $exception): ServiceException
    {
        return new ServiceException($exception->getMessage(), $exception->getCode(), $exception);
    }
}
