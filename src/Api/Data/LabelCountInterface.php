<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api\Data;

/**
 * Number of labels per page in X and Y direction.
 *
 * @api
 */
interface LabelCountInterface
{
    public function getLabelX(): int;

    public function getLabelY(): int;
}
