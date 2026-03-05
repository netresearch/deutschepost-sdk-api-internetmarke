<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api;

use DeutschePost\Sdk\Internetmarke\Api\Data\UserProfileInterface;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceException;

/**
 * Retrieves the authenticated user's profile.
 *
 * @api
 */
interface UserProfileServiceInterface
{
    /**
     * @throws ServiceException
     */
    public function getProfile(): UserProfileInterface;
}
