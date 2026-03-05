<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model\ResponseType;

use DeutschePost\Sdk\Internetmarke\Model\ApiInfo;

/**
 * Envelope for the API info endpoint response: {"amp": {...}}.
 *
 * @internal
 */
class ApiInfoResponse
{
    private ?ApiInfo $amp = null;

    public function getAmp(): ApiInfo
    {
        return $this->amp ?? new ApiInfo();
    }
}
