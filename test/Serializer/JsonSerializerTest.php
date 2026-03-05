<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Serializer;

use DeutschePost\Sdk\Internetmarke\Model\ApiInfo;
use DeutschePost\Sdk\Internetmarke\Serializer\JsonSerializer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class JsonSerializerTest extends TestCase
{
    #[Test]
    public function decodeThrowsOnMalformedJson(): void
    {
        $serializer = new JsonSerializer();

        $this->expectException(\JsonException::class);

        $serializer->decode('not valid json{', ApiInfo::class);
    }

    #[Test]
    public function decodeIgnoresUnexpectedExtraFields(): void
    {
        $json = json_encode([
            'name' => 'Internetmarke',
            'version' => '1.0',
            'rev' => 'abc123',
            'env' => 'production',
            'unexpectedField' => 'should be ignored',
            'anotherExtra' => 42,
        ], JSON_THROW_ON_ERROR);

        $serializer = new JsonSerializer();
        $result = $serializer->decode($json, ApiInfo::class);

        self::assertInstanceOf(ApiInfo::class, $result);
        self::assertSame('Internetmarke', $result->getName());
        self::assertSame('1.0', $result->getVersion());
    }

    #[Test]
    public function decodeHandlesMissingOptionalFields(): void
    {
        $json = json_encode([
            'name' => 'Internetmarke',
        ], JSON_THROW_ON_ERROR);

        $serializer = new JsonSerializer();
        $result = $serializer->decode($json, ApiInfo::class);

        self::assertInstanceOf(ApiInfo::class, $result);
        self::assertSame('Internetmarke', $result->getName());
        self::assertSame('', $result->getVersion());
        self::assertSame('', $result->getRev());
        self::assertSame('', $result->getEnv());
    }
}
