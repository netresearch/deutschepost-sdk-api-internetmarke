<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Exception;

use Http\Client\Exception as HttpClientException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP API error that is both an SDK DetailedServiceException
 * and an HTTPlug exception. This dual hierarchy lets the PluginClient
 * propagate HTTP errors while services catch them as SDK types without
 * manual conversion.
 *
 * @internal
 */
class DetailedErrorHttpException extends DetailedServiceException implements HttpClientException
{
    private readonly RequestInterface $request;

    public function __construct(
        string $message,
        RequestInterface $request,
        private readonly ResponseInterface $response,
    ) {
        $this->request = $request->withoutHeader('Authorization');
        parent::__construct($message, $response->getStatusCode());
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
