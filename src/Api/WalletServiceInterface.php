<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api;

use DeutschePost\Sdk\Internetmarke\Api\Data\WalletChargeInterface;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceException;

/**
 * Charges the Portokasse wallet balance.
 *
 * @api
 */
interface WalletServiceInterface
{
    /**
     * Charge the wallet with the given amount.
     *
     * @param int $amount Amount in euro cents.
     * @throws ServiceException
     */
    public function chargeWallet(int $amount): WalletChargeInterface;
}
