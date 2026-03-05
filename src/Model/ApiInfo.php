<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model;

use DeutschePost\Sdk\Internetmarke\Api\Data\ApiInfoInterface;

/**
 * API version and environment information.
 *
 * Not declared readonly: mutable properties provide safe defaults for optional API fields that JsonMapper may not populate.
 * Treat as immutable — the public API exposes only getters.
 *
 * @api
 */
class ApiInfo implements ApiInfoInterface
{
    private ?string $name = null;
    private ?string $version = null;
    private ?string $rev = null;
    private ?string $env = null;
    private ?string $description = null;

    public function getName(): string
    {
        return $this->name ?? '';
    }

    public function getVersion(): string
    {
        return $this->version ?? '';
    }

    public function getRev(): string
    {
        return $this->rev ?? '';
    }

    public function getEnv(): string
    {
        return $this->env ?? '';
    }

    public function getDescription(): string
    {
        return $this->description ?? '';
    }
}
