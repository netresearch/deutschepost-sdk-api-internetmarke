<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Model\ResponseType;

use DeutschePost\Sdk\Internetmarke\Api\Data\LabelCountInterface;

/**
 * Number of labels per page in X and Y direction.
 *
 * @internal
 */
class LabelCount implements LabelCountInterface
{
    private ?int $labelX = null;

    private ?int $labelY = null;

    public function getLabelX(): int
    {
        return $this->labelX ?? 0;
    }

    public function getLabelY(): int
    {
        return $this->labelY ?? 0;
    }
}
