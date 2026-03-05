<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api;

use DeutschePost\Sdk\Internetmarke\Api\Data\ApiInfoInterface;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceException;

/**
 * Retrieves API version and health status.
 *
 * @api
 */
interface ApiInfoServiceInterface
{
    /**
     * @throws ServiceException
     */
    public function getInfo(): ApiInfoInterface;

    public function isHealthy(): bool;
}
