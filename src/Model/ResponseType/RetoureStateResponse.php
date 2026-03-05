<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model\ResponseType;

use DeutschePost\Sdk\Internetmarke\Model\RetoureState;

/**
 * Envelope for the retoure state endpoint response.
 *
 * The API returns {"RetrieveRetoureStateResponse": [{...}, ...]}.
 * The property name must match the JSON key exactly for JsonMapper.
 *
 * @internal
 */
class RetoureStateResponse
{
    /** @var \DeutschePost\Sdk\Internetmarke\Model\RetoureState[]|null */
    private ?array $RetrieveRetoureStateResponse = null;

    /**
     * @return RetoureState[]
     */
    public function getRetoureStates(): array
    {
        return $this->RetrieveRetoureStateResponse ?? [];
    }
}
