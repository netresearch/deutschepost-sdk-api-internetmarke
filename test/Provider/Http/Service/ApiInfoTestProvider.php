<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Provider\Http\Service;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;

class ApiInfoTestProvider
{
    private static function loadFixture(string $path): string
    {
        return file_get_contents(__DIR__ . '/../../_files/' . $path);
    }

    /**
     * @return ResponseInterface[]
     */
    public static function getInfoSuccess(): array
    {
        $factory = new Psr17Factory();

        return [
            $factory->createResponse(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream(self::loadFixture('apiinfo/success.json'))),
        ];
    }
}
