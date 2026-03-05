<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api\Data;

/**
 * API version and environment information.
 *
 * @api
 */
interface ApiInfoInterface
{
    public function getName(): string;

    public function getVersion(): string;

    public function getRev(): string;

    public function getEnv(): string;

    public function getDescription(): string;
}
