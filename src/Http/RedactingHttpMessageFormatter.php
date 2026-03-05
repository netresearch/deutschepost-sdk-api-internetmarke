<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Http;

use Http\Message\Formatter;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP message formatter that redacts sensitive data from log output.
 *
 * Prevents credential leakage by redacting Authorization headers,
 * OAuth secrets in request bodies, and tokens in response bodies.
 * Binary content (PDF, PNG, octet-stream) is replaced with a size indicator.
 *
 * @internal
 */
final class RedactingHttpMessageFormatter implements Formatter
{
    private const int MAX_BODY_LENGTH = 1000;

    private const string REDACTED = '[REDACTED]';

    /**
     * Header names whose values must be fully redacted (case-insensitive matching
     * is handled by PSR-7 getHeaders() returning the original case, so we
     * normalize to lowercase for comparison).
     *
     * @var list<string>
     */
    private const array SENSITIVE_HEADERS = [
        'authorization',
    ];

    /**
     * Form-encoded body parameter names that carry secrets.
     *
     * @var list<string>
     */
    private const array SENSITIVE_FORM_PARAMS = [
        'client_secret',
        'password',
    ];

    /**
     * JSON response body keys that carry tokens.
     *
     * @var list<string>
     */
    private const array SENSITIVE_JSON_KEYS = [
        'access_token',
        'refresh_token',
    ];

    /**
     * Content-Type values that indicate binary payloads.
     *
     * @var list<string>
     */
    private const array BINARY_CONTENT_TYPES = [
        'application/pdf',
        'image/png',
        'application/octet-stream',
    ];

    public function formatRequest(RequestInterface $request): string
    {
        $message = sprintf(
            "%s %s HTTP/%s\n",
            $request->getMethod(),
            $request->getRequestTarget(),
            $request->getProtocolVersion(),
        );

        foreach ($request->getHeaders() as $name => $values) {
            $headerValue = implode(', ', $values);

            if (in_array(strtolower((string) $name), self::SENSITIVE_HEADERS, true)) {
                $headerValue = self::REDACTED;
            }

            $message .= $name . ': ' . $headerValue . "\n";
        }

        $message .= "\n";

        return $message . $this->formatRequestBody($request);
    }

    public function formatResponse(ResponseInterface $response): string
    {
        $message = sprintf(
            "HTTP/%s %s %s\n",
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase(),
        );

        foreach ($response->getHeaders() as $name => $values) {
            $message .= $name . ': ' . implode(', ', $values) . "\n";
        }

        $message .= "\n";

        return $message . $this->formatResponseBody($response);
    }

    public function formatResponseForRequest(ResponseInterface $response, RequestInterface $request): string
    {
        return $this->formatResponse($response);
    }

    private function formatRequestBody(RequestInterface $request): string
    {
        $stream = $request->getBody();

        if (!$stream->isSeekable()) {
            return '';
        }

        $body = $stream->__toString();
        $stream->rewind();

        if ($body === '') {
            return '';
        }

        $contentType = $request->getHeaderLine('Content-Type');

        if ($this->isBinaryContentType($contentType)) {
            return sprintf('[binary content, %d bytes]', strlen($body));
        }

        if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            $body = $this->redactFormParams($body);
        }

        return $this->truncate($body);
    }

    private function formatResponseBody(ResponseInterface $response): string
    {
        $stream = $response->getBody();

        if (!$stream->isSeekable()) {
            return '';
        }

        $body = $stream->__toString();
        $stream->rewind();

        if ($body === '') {
            return '';
        }

        $contentType = $response->getHeaderLine('Content-Type');

        if ($this->isBinaryContentType($contentType)) {
            return sprintf('[binary content, %d bytes]', strlen($body));
        }

        if (str_contains($contentType, 'application/json')) {
            $body = $this->redactJsonTokens($body);
        }

        return $this->truncate($body);
    }

    private function isBinaryContentType(string $contentType): bool
    {
        foreach (self::BINARY_CONTENT_TYPES as $binaryType) {
            if (str_contains($contentType, $binaryType)) {
                return true;
            }
        }

        return false;
    }

    private function redactFormParams(string $body): string
    {
        foreach (self::SENSITIVE_FORM_PARAMS as $param) {
            $body = preg_replace(
                '/((?:^|&)' . preg_quote($param, '/') . '=)[^&]*/i',
                '$1' . self::REDACTED,
                $body,
            ) ?? $body;
        }

        return $body;
    }

    private function redactJsonTokens(string $body): string
    {
        /** @var mixed $data */
        $data = json_decode($body, true);

        if (!is_array($data)) {
            return $body;
        }

        foreach (self::SENSITIVE_JSON_KEYS as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = self::REDACTED;
            }
        }

        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    private function truncate(string $body): string
    {
        if (mb_strlen($body) > self::MAX_BODY_LENGTH) {
            return mb_substr($body, 0, self::MAX_BODY_LENGTH) . '... [truncated]';
        }

        return $body;
    }
}
