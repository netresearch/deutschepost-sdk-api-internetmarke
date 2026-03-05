<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Provider\Http\Service;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;

class RefundTestProvider
{
    private static function loadFixture(string $path): string
    {
        return file_get_contents(__DIR__ . '/../../_files/' . $path);
    }

    /**
     * Auth + refund response.
     *
     * @return ResponseInterface[]
     */
    public static function requestRefundSuccess(): array
    {
        $factory = new Psr17Factory();

        return [
            $factory->createResponse(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream(self::loadFixture('authentication/success.json'))),
            $factory->createResponse(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream(self::loadFixture('refund/requestRefundSuccess.json'))),
        ];
    }

    /**
     * Auth + retoure state response.
     *
     * @return ResponseInterface[]
     */
    public static function retoureStateSuccess(): array
    {
        $factory = new Psr17Factory();

        return [
            $factory->createResponse(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream(self::loadFixture('authentication/success.json'))),
            $factory->createResponse(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream(self::loadFixture('refund/retoureStateSuccess.json'))),
        ];
    }
}
