<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api;

use DeutschePost\Sdk\Internetmarke\Api\Data\RefundInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\RetoureStateInterface;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceException;
use DeutschePost\Sdk\Internetmarke\Model\RefundRequest;

/**
 * Requests refunds and queries retoure transaction state.
 *
 * @api
 */
interface RefundServiceInterface
{
    /**
     * @throws ServiceException
     */
    public function requestRefund(RefundRequest $request): RefundInterface;

    /**
     * @return RetoureStateInterface[]
     * @throws ServiceException
     */
    public function getRetoureState(
        ?string $shopRetoureId = null,
        ?int $retoureTransactionId = null,
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null,
    ): array;
}
