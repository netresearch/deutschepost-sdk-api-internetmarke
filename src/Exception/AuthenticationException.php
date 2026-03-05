<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Exception;

/**
 * Thrown on authentication failures (invalid credentials, expired tokens).
 *
 * @api
 */
class AuthenticationException extends DetailedServiceException
{
}
