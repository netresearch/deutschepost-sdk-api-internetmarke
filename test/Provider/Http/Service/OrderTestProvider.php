<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Provider\Http\Service;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;

class OrderTestProvider
{
    private static function loadFixture(string $path): string
    {
        return file_get_contents(__DIR__ . '/../../_files/' . $path);
    }

    private static function binaryResponse(Psr17Factory $factory, string $content): ResponseInterface
    {
        return $factory->createResponse(200)
            ->withHeader('Content-Type', 'application/pdf')
            ->withBody($factory->createStream($content));
    }

    /**
     * Auth + initCart response.
     *
     * @return ResponseInterface[]
     */
    public static function initCartSuccess(): array
    {
        $factory = new Psr17Factory();

        return [
            $factory->createResponse(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream(self::loadFixture('authentication/success.json'))),
            $factory->createResponse(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream(self::loadFixture('order/initCartSuccess.json'))),
        ];
    }

    /**
     * Auth + checkout PDF response + label download + manifest download.
     *
     * @return ResponseInterface[]
     */
    public static function checkoutPdfSuccess(): array
    {
        $factory = new Psr17Factory();

        return [
            $factory->createResponse(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream(self::loadFixture('authentication/success.json'))),
            $factory->createResponse(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream(self::loadFixture('order/checkoutPdfSuccess.json'))),
            self::binaryResponse($factory, '%PDF-1.4 fake label content'),
            self::binaryResponse($factory, '%PDF-1.4 fake manifest content'),
        ];
    }

    /**
     * Auth + checkout PNG response + label download (no manifest).
     *
     * @return ResponseInterface[]
     */
    public static function checkoutPngSuccess(): array
    {
        $factory = new Psr17Factory();

        return [
            $factory->createResponse(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream(self::loadFixture('authentication/success.json'))),
            $factory->createResponse(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream(self::loadFixture('order/checkoutPngSuccess.json'))),
            self::binaryResponse($factory, 'PNG fake label content'),
        ];
    }

    /**
     * Auth + retrieve order response + label download (empty manifestLink).
     *
     * @return ResponseInterface[]
     */
    public static function retrieveOrderSuccess(): array
    {
        $factory = new Psr17Factory();

        return [
            $factory->createResponse(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream(self::loadFixture('authentication/success.json'))),
            $factory->createResponse(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream(self::loadFixture('order/retrieveOrderSuccess.json'))),
            self::binaryResponse($factory, '%PDF-1.4 fake label content'),
        ];
    }
}
