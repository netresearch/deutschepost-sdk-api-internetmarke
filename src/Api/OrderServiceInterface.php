<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Api;

use DeutschePost\Sdk\Internetmarke\Api\Data\OrderInterface;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceException;
use DeutschePost\Sdk\Internetmarke\Model\OrderRequest;
use DeutschePost\Sdk\Internetmarke\Model\PdfPreviewRequest;
use DeutschePost\Sdk\Internetmarke\Model\PngOrderRequest;
use DeutschePost\Sdk\Internetmarke\Model\PngPreviewRequest;

/**
 * Creates, retrieves, and manages voucher orders with PDF or PNG labels.
 *
 * @api
 */
interface OrderServiceInterface
{
    /**
     * Initialize an empty shopping cart.
     *
     * @return string The shopOrderId for the created cart.
     * @throws ServiceException
     */
    public function initializeCart(): string;

    /**
     * Create a PDF voucher order via directCheckout.
     *
     * @throws ServiceException
     */
    public function createOrder(OrderRequest $request): OrderInterface;

    /**
     * Create a PNG voucher order via directCheckout.
     *
     * @throws ServiceException
     */
    public function createPngOrder(PngOrderRequest $request): OrderInterface;

    /**
     * Preview a single PDF voucher without charging the wallet.
     *
     * @throws ServiceException
     */
    public function previewPdfOrder(PdfPreviewRequest $request): OrderInterface;

    /**
     * Preview a single PNG voucher without charging the wallet.
     *
     * @throws ServiceException
     */
    public function previewPngOrder(PngPreviewRequest $request): OrderInterface;

    /**
     * Retrieve an existing order by its shopOrderId.
     *
     * @throws ServiceException
     */
    public function getOrder(string $shopOrderId): OrderInterface;
}
