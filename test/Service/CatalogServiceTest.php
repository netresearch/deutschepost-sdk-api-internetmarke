<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Service;

use DeutschePost\Sdk\Internetmarke\Api\Data\CatalogItemInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\ContractProductInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\ImageItemInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\MotiveLinkInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\PageFormatInterface;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceException;
use DeutschePost\Sdk\Internetmarke\Serializer\JsonSerializer;
use DeutschePost\Sdk\Internetmarke\Service\CatalogService;
use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CatalogServiceTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    private function createCatalogResponse(): \Psr\Http\Message\ResponseInterface
    {
        $body = json_encode([
            'pageFormats' => [
                [
                    'id' => 1,
                    'name' => 'DIN A4 Normalpapier',
                    'description' => 'DIN A4 plain paper for ink jet and laser printers',
                    'isAddressPossible' => true,
                    'isImagePossible' => true,
                    'pageType' => 'REGULARPAGE',
                    'pageLayout' => [
                        'size' => ['x' => 210.0, 'y' => 297.0],
                        'orientation' => 'LANDSCAPE',
                        'labelSpacing' => ['x' => 0.0, 'y' => 0.0],
                        'labelCount' => ['labelX' => 2, 'labelY' => 5],
                        'margin' => ['top' => 5.0, 'bottom' => 5.0, 'left' => 5.0, 'right' => 5.0],
                    ],
                ],
                [
                    'id' => 2,
                    'name' => 'DIN A4 Etiketten',
                    'description' => 'Labels for DIN A4 sheets',
                    'isAddressPossible' => false,
                    'isImagePossible' => false,
                    'pageType' => 'LABELPAGE',
                    'pageLayout' => [
                        'size' => ['x' => 210.0, 'y' => 297.0],
                        'orientation' => 'PORTRAIT',
                        'labelSpacing' => ['x' => 3.0, 'y' => 0.0],
                        'labelCount' => ['labelX' => 2, 'labelY' => 7],
                        'margin' => ['top' => 12.7, 'bottom' => 12.7, 'left' => 4.7, 'right' => 4.7],
                    ],
                ],
            ],
            'contractProducts' => [
                'products' => [
                    ['productCode' => 10001, 'price' => 85],
                    ['productCode' => 10002, 'price' => 160],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        return $this->factory->createResponse(200)->withBody($this->factory->createStream($body));
    }

    #[Test]
    public function getPageFormatsReturnsPageFormatObjects(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse($this->createCatalogResponse());

        $service = new CatalogService(
            $mockClient,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );

        $formats = $service->getPageFormats();

        self::assertCount(2, $formats);
        self::assertContainsOnlyInstancesOf(PageFormatInterface::class, $formats);

        $format = $formats[0];
        self::assertSame(1, $format->getId());
        self::assertSame('DIN A4 Normalpapier', $format->getName());
        self::assertSame('DIN A4 plain paper for ink jet and laser printers', $format->getDescription());
        self::assertTrue($format->isAddressPossible());
        self::assertTrue($format->isImagePossible());
        self::assertSame('REGULARPAGE', $format->getPageType());

        $layout = $format->getPageLayout();
        self::assertSame('LANDSCAPE', $layout->getOrientation());
        self::assertSame(210.0, $layout->getSize()->getX());
        self::assertSame(297.0, $layout->getSize()->getY());
        self::assertSame(2, $layout->getLabelCount()->getLabelX());
        self::assertSame(5, $layout->getLabelCount()->getLabelY());
        self::assertSame(0.0, $layout->getLabelSpacing()->getX());
        self::assertSame(0.0, $layout->getLabelSpacing()->getY());
        self::assertSame(5.0, $layout->getMargin()->getTop());
        self::assertSame(5.0, $layout->getMargin()->getBottom());
        self::assertSame(5.0, $layout->getMargin()->getLeft());
        self::assertSame(5.0, $layout->getMargin()->getRight());

        self::assertSame(2, $formats[1]->getId());
        self::assertFalse($formats[1]->isAddressPossible());
        self::assertSame('LABELPAGE', $formats[1]->getPageType());
        self::assertSame('PORTRAIT', $formats[1]->getPageLayout()->getOrientation());
    }

    #[Test]
    public function getPageFormatsSendsCorrectQueryParameter(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse($this->createCatalogResponse());

        $service = new CatalogService(
            $mockClient,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );

        $service->getPageFormats();

        $lastRequest = $mockClient->getLastRequest();
        self::assertStringContainsString('/app/catalog', (string) $lastRequest->getUri());
        self::assertStringContainsString('types=PAGE_FORMATS', (string) $lastRequest->getUri());
    }

    #[Test]
    public function getContractProductsReturnsProductObjects(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse($this->createCatalogResponse());

        $service = new CatalogService(
            $mockClient,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );

        $products = $service->getContractProducts();

        self::assertCount(2, $products);
        self::assertContainsOnlyInstancesOf(ContractProductInterface::class, $products);

        self::assertSame(10001, $products[0]->getProductCode());
        self::assertSame(85, $products[0]->getPrice());

        self::assertSame(10002, $products[1]->getProductCode());
        self::assertSame(160, $products[1]->getPrice());
    }

    #[Test]
    public function getContractProductsSendsPublicTypesParam(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse($this->createCatalogResponse());

        $service = new CatalogService(
            $mockClient,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );

        $service->getContractProducts();

        $lastRequest = $mockClient->getLastRequest();
        $uri = (string) $lastRequest->getUri();
        self::assertStringContainsString('/app/catalog', $uri);
        self::assertStringContainsString('types=PUBLIC', $uri);
    }

    #[Test]
    public function getPageFormatsReturnsEmptyArrayWhenCatalogIsEmpty(): void
    {
        $body = json_encode([
            'pageFormats' => [],
            'contractProducts' => ['products' => []],
        ], JSON_THROW_ON_ERROR);

        $response = $this->factory->createResponse(200)->withBody($this->factory->createStream($body));

        $mockClient = new MockClient();
        $mockClient->addResponse($response);

        $service = new CatalogService(
            $mockClient,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );

        self::assertSame([], $service->getPageFormats());
    }

    #[Test]
    public function getContractProductsReturnsEmptyArrayWhenCatalogIsEmpty(): void
    {
        $body = json_encode([
            'pageFormats' => [],
            'contractProducts' => ['products' => []],
        ], JSON_THROW_ON_ERROR);

        $response = $this->factory->createResponse(200)->withBody($this->factory->createStream($body));

        $mockClient = new MockClient();
        $mockClient->addResponse($response);

        $service = new CatalogService(
            $mockClient,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );

        self::assertSame([], $service->getContractProducts());
    }

    // --- publicGallery tests ---

    private function createFullCatalogResponse(): \Psr\Http\Message\ResponseInterface
    {
        $body = json_encode([
            'pageFormats' => [],
            'contractProducts' => ['products' => []],
            'publicGallery' => [
                'items' => [
                    [
                        'category' => 'Animals',
                        'categoryDescription' => 'Motifs with animals',
                        'categoryId' => 1,
                        'images' => [
                            [
                                'imageID' => 101,
                                'imageDescription' => 'Cat on a fence',
                                'imageSlogan' => 'Purr-fect delivery',
                                'links' => [
                                    'link' => 'https://example.com/images/cat.png',
                                    'linkThumbnail' => 'https://example.com/images/cat_thumb.png',
                                ],
                            ],
                            [
                                'imageID' => 102,
                                'imageDescription' => 'Dog in a park',
                                'imageSlogan' => 'Fetch your mail',
                                'links' => [
                                    'link' => 'https://example.com/images/dog.png',
                                    'linkThumbnail' => 'https://example.com/images/dog_thumb.png',
                                ],
                            ],
                        ],
                    ],
                    [
                        'category' => 'Flowers',
                        'categoryDescription' => 'Floral motifs',
                        'categoryId' => 2,
                        'images' => [],
                    ],
                ],
            ],
            'privateGallery' => [
                'imageLink' => [
                    [
                        'link' => 'https://example.com/private/logo.png',
                        'linkThumbnail' => 'https://example.com/private/logo_thumb.png',
                    ],
                    [
                        'link' => 'https://example.com/private/banner.png',
                        'linkThumbnail' => 'https://example.com/private/banner_thumb.png',
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        return $this->factory->createResponse(200)->withBody($this->factory->createStream($body));
    }

    #[Test]
    public function getPublicCatalogReturnsCatalogItems(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse($this->createFullCatalogResponse());

        $service = new CatalogService(
            $mockClient,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );

        $items = $service->getPublicCatalog();

        self::assertCount(2, $items);
        self::assertContainsOnlyInstancesOf(CatalogItemInterface::class, $items);

        $animals = $items[0];
        self::assertSame('Animals', $animals->getCategory());
        self::assertSame('Motifs with animals', $animals->getCategoryDescription());
        self::assertSame(1, $animals->getCategoryId());

        $images = $animals->getImages();
        self::assertCount(2, $images);
        self::assertContainsOnlyInstancesOf(ImageItemInterface::class, $images);

        $cat = $images[0];
        self::assertSame(101, $cat->getImageID());
        self::assertSame('Cat on a fence', $cat->getImageDescription());
        self::assertSame('Purr-fect delivery', $cat->getImageSlogan());

        $links = $cat->getLinks();
        self::assertInstanceOf(MotiveLinkInterface::class, $links);
        self::assertSame('https://example.com/images/cat.png', $links->getLink());
        self::assertSame('https://example.com/images/cat_thumb.png', $links->getLinkThumbnail());

        $flowers = $items[1];
        self::assertSame('Flowers', $flowers->getCategory());
        self::assertSame(2, $flowers->getCategoryId());
        self::assertSame([], $flowers->getImages());
    }

    #[Test]
    public function getPublicCatalogSendsPublicTypesParam(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse($this->createFullCatalogResponse());

        $service = new CatalogService(
            $mockClient,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );

        $service->getPublicCatalog();

        $uri = (string) $mockClient->getLastRequest()->getUri();
        self::assertStringContainsString('/app/catalog', $uri);
        self::assertStringContainsString('types=PUBLIC', $uri);
    }

    #[Test]
    public function getPublicCatalogReturnsEmptyArrayWhenMissing(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse($this->createCatalogResponse());

        $service = new CatalogService(
            $mockClient,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );

        self::assertSame([], $service->getPublicCatalog());
    }

    // --- privateGallery tests ---

    #[Test]
    public function getPrivateCatalogReturnsMotiveLinks(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse($this->createFullCatalogResponse());

        $service = new CatalogService(
            $mockClient,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );

        $links = $service->getPrivateCatalog();

        // No PRIVATE catalog type exists in the API spec — types=PUBLIC is the only valid option.
        $uri = (string) $mockClient->getLastRequest()->getUri();
        self::assertStringContainsString('types=PUBLIC', $uri);

        self::assertCount(2, $links);
        self::assertContainsOnlyInstancesOf(MotiveLinkInterface::class, $links);

        self::assertSame('https://example.com/private/logo.png', $links[0]->getLink());
        self::assertSame('https://example.com/private/logo_thumb.png', $links[0]->getLinkThumbnail());

        self::assertSame('https://example.com/private/banner.png', $links[1]->getLink());
        self::assertSame('https://example.com/private/banner_thumb.png', $links[1]->getLinkThumbnail());
    }

    #[Test]
    public function getPrivateCatalogReturnsEmptyArrayWhenMissing(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse($this->createCatalogResponse());

        $service = new CatalogService(
            $mockClient,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );

        self::assertSame([], $service->getPrivateCatalog());
    }

    #[Test]
    public function cachesResponseForSameCatalogType(): void
    {
        $mockClient = new MockClient();
        // Queue only ONE response — second call must use cache
        $mockClient->addResponse($this->createFullCatalogResponse());

        $service = new CatalogService(
            $mockClient,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );

        // First call fetches from API
        $items = $service->getPublicCatalog();
        self::assertCount(2, $items);

        // Second call with same type (PUBLIC) uses cache — no HTTP request
        $products = $service->getContractProducts();
        self::assertIsArray($products);

        // Third call with same type (PUBLIC) also uses cache
        $links = $service->getPrivateCatalog();
        self::assertCount(2, $links);
    }

    #[Test]
    public function throwsServiceExceptionOnApiError(): void
    {
        $mockClient = new MockClient();
        $mockClient->addException(new \RuntimeException('timeout'));

        $service = new CatalogService(
            $mockClient,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );

        $this->expectException(ServiceException::class);

        $service->getPageFormats();
    }
}
