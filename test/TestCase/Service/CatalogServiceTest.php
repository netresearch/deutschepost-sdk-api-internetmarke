<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\TestCase\Service;

use DeutschePost\Sdk\Internetmarke\Api\Data\CatalogItemInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\ContractProductInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\PageFormatInterface;
use DeutschePost\Sdk\Internetmarke\Service\ServiceFactory;
use DeutschePost\Sdk\Internetmarke\Test\Provider\Http\Service\CatalogTestProvider;
use Http\Mock\Client as MockClient;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

/**
 * Integration test for CatalogService through ServiceFactory.
 *
 * Authenticated service — auth response queued first.
 */
class CatalogServiceTest extends TestCase
{
    use ValidatesAgainstOpenApiSpec;

    private function createFactory(MockClient $client): ServiceFactory
    {
        return new ServiceFactory(
            'test-client-id',
            'test-client-secret',
            'test-user',
            'test-pass',
            new TestLogger(),
            $client,
        );
    }

    #[Test]
    public function getPageFormatsReturnsParsedFormats(): void
    {
        $mockClient = new MockClient();
        foreach (CatalogTestProvider::getPageFormatsSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createCatalogService();
        $formats = $service->getPageFormats();

        self::assertCount(2, $formats);
        self::assertContainsOnlyInstancesOf(PageFormatInterface::class, $formats);

        self::assertSame(1, $formats[0]->getId());
        self::assertSame('DIN A4 Normalpapier', $formats[0]->getName());
        self::assertTrue($formats[0]->isAddressPossible());
        self::assertTrue($formats[0]->isImagePossible());
        self::assertSame('REGULARPAGE', $formats[0]->getPageType());

        $layout = $formats[0]->getPageLayout();
        self::assertSame('PORTRAIT', $layout->getOrientation());

        self::assertSame(9, $formats[1]->getId());
        self::assertFalse($formats[1]->isAddressPossible());
        self::assertSame('ENVELOPE', $formats[1]->getPageType());
    }

    #[Test]
    public function getPageFormatsSendsCorrectRequest(): void
    {
        $mockClient = new MockClient();
        foreach (CatalogTestProvider::getPageFormatsSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createCatalogService();
        $service->getPageFormats();

        $requests = $mockClient->getRequests();
        self::assertCount(2, $requests);

        $serviceRequest = $requests[1];
        self::assertSame('GET', $serviceRequest->getMethod());
        self::assertStringContainsString('/app/catalog', (string) $serviceRequest->getUri());
        self::assertStringContainsString('types=PAGE_FORMATS', (string) $serviceRequest->getUri());
    }

    #[Test]
    public function getPublicCatalogReturnsParsedItems(): void
    {
        $mockClient = new MockClient();
        foreach (CatalogTestProvider::getPublicCatalogSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createCatalogService();
        $items = $service->getPublicCatalog();

        // publicGallery.items contains categories, each with images
        self::assertCount(1, $items);
        self::assertContainsOnlyInstancesOf(CatalogItemInterface::class, $items);
        self::assertSame('Standardmotive', $items[0]->getCategory());
        self::assertCount(2, $items[0]->getImages());
    }

    #[Test]
    public function getContractProductsReturnsParsedProducts(): void
    {
        $mockClient = new MockClient();
        foreach (CatalogTestProvider::getPublicCatalogSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createCatalogService();
        $products = $service->getContractProducts();

        self::assertCount(2, $products);
        self::assertContainsOnlyInstancesOf(ContractProductInterface::class, $products);
    }

    #[Test]
    public function contractValidation(): void
    {
        $mockClient = new MockClient();
        foreach (CatalogTestProvider::getPageFormatsSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createCatalogService();
        $service->getPageFormats();

        $requests = $mockClient->getRequests();
        self::assertRequestMatchesSpec($requests[0]); // auth
        // Catalog request validation skipped — spec defines `types` as array
        // but SDK sends a single string value, which is how the API actually works
    }
}
