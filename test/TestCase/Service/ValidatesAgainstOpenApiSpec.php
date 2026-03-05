<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\TestCase\Service;

use League\OpenAPIValidation\PSR7\OperationAddress;
use League\OpenAPIValidation\PSR7\RequestValidator;
use League\OpenAPIValidation\PSR7\ResponseValidator;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Contract validation against the OpenAPI specification.
 *
 * Validates captured requests and fixture responses against the spec
 * to ensure the SDK stays compatible with the API contract.
 */
trait ValidatesAgainstOpenApiSpec
{
    private static ?RequestValidator $requestValidator = null;

    private static ?ResponseValidator $responseValidator = null;

    private static function specPath(): string
    {
        return __DIR__ . '/../../resources/pp-post-internetmarke.yaml';
    }

    private static function requestValidator(): RequestValidator
    {
        if (!self::$requestValidator instanceof \League\OpenAPIValidation\PSR7\RequestValidator) {
            self::$requestValidator = (new ValidatorBuilder())
                ->fromYamlFile(self::specPath())
                ->getRequestValidator();
        }

        return self::$requestValidator;
    }

    private static function responseValidator(): ResponseValidator
    {
        if (!self::$responseValidator instanceof \League\OpenAPIValidation\PSR7\ResponseValidator) {
            self::$responseValidator = (new ValidatorBuilder())
                ->fromYamlFile(self::specPath())
                ->getResponseValidator();
        }

        return self::$responseValidator;
    }

    /**
     * Validate that a captured request conforms to the OpenAPI spec.
     *
     * Returns the matched OperationAddress for subsequent response validation.
     */
    private static function assertRequestMatchesSpec(RequestInterface $request): OperationAddress
    {
        $operation = self::requestValidator()->validate($request);
        Assert::assertTrue(true, 'Request matches OpenAPI spec');

        return $operation;
    }

    /**
     * Validate that a response conforms to the OpenAPI spec for the given operation.
     */
    private static function assertResponseMatchesSpec(
        OperationAddress $operation,
        ResponseInterface $response,
    ): void {
        self::responseValidator()->validate($operation, $response);
        Assert::assertTrue(true, 'Response matches OpenAPI spec');
    }
}
