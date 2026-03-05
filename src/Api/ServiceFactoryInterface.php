<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api;

/**
 * Primary SDK entry point. Creates ready-to-use service instances.
 *
 * @api
 */
interface ServiceFactoryInterface
{
    /**
     * Create a service for retrieving API version and health status.
     */
    public function createApiInfoService(): ApiInfoServiceInterface;

    /**
     * Create a service for retrieving page formats, contract products, and motif image catalogs.
     */
    public function createCatalogService(): CatalogServiceInterface;

    /**
     * Create a service for creating, retrieving, and managing voucher orders.
     */
    public function createOrderService(): OrderServiceInterface;

    /**
     * Create a service for requesting refunds and querying retoure state.
     */
    public function createRefundService(): RefundServiceInterface;

    /**
     * Create a service for retrieving the authenticated user's profile.
     */
    public function createUserProfileService(): UserProfileServiceInterface;

    /**
     * Create a service for charging the Portokasse wallet balance.
     */
    public function createWalletService(): WalletServiceInterface;
}
