<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Service;

use DeutschePost\Sdk\Internetmarke\Api\CatalogServiceInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\CatalogItemInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\ContractProductInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\MotiveLinkInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\PageFormatInterface;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceException;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceExceptionFactory;
use DeutschePost\Sdk\Internetmarke\Model\ResponseType\CatalogResponse;
use DeutschePost\Sdk\Internetmarke\Model\ResponseType\ContractProducts;
use DeutschePost\Sdk\Internetmarke\Model\ResponseType\PrivateCatalog;
use DeutschePost\Sdk\Internetmarke\Model\ResponseType\PublicCatalog;
use DeutschePost\Sdk\Internetmarke\Serializer\JsonSerializer;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * Retrieves page formats, contract products, and motif image catalogs.
 *
 * Not declared readonly: the in-memory cache requires a mutable property.
 *
 * @internal
 */
class CatalogService implements CatalogServiceInterface
{
    /** @var array<string, CatalogResponse> */
    private array $cache = [];

    public function __construct(
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly JsonSerializer $serializer,
        private readonly string $baseUrl,
    ) {
    }

    /**
     * @return PageFormatInterface[]
     * @throws ServiceException
     */
    public function getPageFormats(): array
    {
        return $this->fetchCatalog('PAGE_FORMATS')->getPageFormats();
    }

    /**
     * @return ContractProductInterface[]
     * @throws ServiceException
     */
    public function getContractProducts(): array
    {
        $wrapper = $this->fetchCatalog('PUBLIC')->getContractProducts();

        return $wrapper instanceof ContractProducts ? $wrapper->getProducts() : [];
    }

    /**
     * @return CatalogItemInterface[]
     * @throws ServiceException
     */
    public function getPublicCatalog(): array
    {
        $catalog = $this->fetchCatalog('PUBLIC')->getPublicCatalog();

        return $catalog instanceof PublicCatalog ? $catalog->getItems() : [];
    }

    /**
     * Retrieve private gallery image links.
     *
     * The OpenAPI spec's `types` enum only defines PUBLIC and PAGE_FORMATS — there is no PRIVATE
     * catalog type. Private gallery data is an optional part of the unified catalog response returned
     * when requesting types=PUBLIC. Whether the API actually populates the `privateGallery` field
     * needs sandbox verification.
     *
     * @return MotiveLinkInterface[]
     * @throws ServiceException
     */
    public function getPrivateCatalog(): array
    {
        $catalog = $this->fetchCatalog('PUBLIC')->getPrivateCatalog();

        return $catalog instanceof PrivateCatalog ? $catalog->getImageLinks() : [];
    }

    /**
     * @throws ServiceException
     */
    private function fetchCatalog(?string $types = null): CatalogResponse
    {
        $cacheKey = $types ?? '__all__';

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            $url = $this->baseUrl . '/app/catalog';
            if ($types !== null) {
                $url .= '?types=' . $types;
            }

            $request = $this->requestFactory->createRequest('GET', $url);
            $response = $this->client->sendRequest($request);

            $result = $this->serializer->decode((string) $response->getBody(), CatalogResponse::class);
            $this->cache[$cacheKey] = $result;

            return $result;
        } catch (\Throwable $e) {
            throw ServiceExceptionFactory::create($e);
        }
    }
}
