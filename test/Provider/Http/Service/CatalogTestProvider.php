<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Provider\Http\Service;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;

class CatalogTestProvider
{
    private static function loadFixture(string $path): string
    {
        return file_get_contents(__DIR__ . '/../../_files/' . $path);
    }

    /**
     * Auth response + page formats response.
     *
     * @return ResponseInterface[]
     */
    public static function getPageFormatsSuccess(): array
    {
        $factory = new Psr17Factory();

        return [
            $factory->createResponse(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream(self::loadFixture('authentication/success.json'))),
            $factory->createResponse(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream(self::loadFixture('catalog/pageFormatsSuccess.json'))),
        ];
    }

    /**
     * Auth response + public catalog response (includes contractProducts).
     *
     * @return ResponseInterface[]
     */
    public static function getPublicCatalogSuccess(): array
    {
        $factory = new Psr17Factory();

        return [
            $factory->createResponse(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream(self::loadFixture('authentication/success.json'))),
            $factory->createResponse(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream(self::loadFixture('catalog/publicCatalogSuccess.json'))),
        ];
    }
}
