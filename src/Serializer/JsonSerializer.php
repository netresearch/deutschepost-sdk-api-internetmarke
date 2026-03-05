<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Serializer;

/**
 * Serializer for outgoing request types and incoming responses.
 *
 * Encodes \JsonSerializable request objects with recursive null/empty filtering.
 * Decodes JSON responses into typed objects via JsonMapper.
 *
 * @internal
 */
class JsonSerializer
{
    private readonly \JsonMapper $jsonMapper;

    public function __construct()
    {
        $this->jsonMapper = new \JsonMapper();
        $this->jsonMapper->bIgnoreVisibility = true;
    }

    /**
     * Encode a request object to JSON, filtering null and empty values.
     *
     * The encode-decode-filter-encode cycle is intentional: json_encode()
     * recursively invokes jsonSerialize() on nested objects, producing a
     * fully-resolved JSON string. Decoding back to array enables recursive
     * null/empty filtering before the final encode.
     *
     * @throws \JsonException
     */
    public function encode(\JsonSerializable $request): string
    {
        $payload = json_encode($request, JSON_THROW_ON_ERROR);
        $payload = (array) json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        $payload = $this->filterRecursive($payload);

        return json_encode($payload, JSON_THROW_ON_ERROR);
    }

    /**
     * Decode a JSON response into a typed object.
     *
     * @template T of object
     * @param class-string<T> $targetClass
     * @return T
     *
     * @throws \JsonMapper_Exception
     * @throws \JsonException
     */
    public function decode(string $json, string $targetClass): object
    {
        $data = json_decode($json, false, 512, JSON_THROW_ON_ERROR);

        /** @var T $mapped */
        $mapped = $this->jsonMapper->map($data, new $targetClass());

        return $mapped;
    }

    /**
     * Recursively filter null, empty strings, and empty arrays.
     *
     * @param mixed[] $element
     * @return mixed[]
     */
    private function filterRecursive(array $element): array
    {
        $filterFunction = static fn ($entry): bool => !in_array($entry, [null, '', []], true);

        foreach ($element as &$value) {
            if (\is_array($value)) {
                $value = $this->filterRecursive($value);
            }
        }

        return array_filter($element, $filterFunction);
    }
}
