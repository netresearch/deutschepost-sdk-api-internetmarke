<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Http;

use DeutschePost\Sdk\Internetmarke\Http\RedactingHttpMessageFormatter;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RedactingHttpMessageFormatterTest extends TestCase
{
    private RedactingHttpMessageFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new RedactingHttpMessageFormatter();
    }

    #[Test]
    public function redactsAuthorizationHeader(): void
    {
        $request = new Request(
            'GET',
            'https://api.example.com/resource',
            [
                'Authorization' => 'Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.secret',
                'Accept' => 'application/json',
            ],
        );

        $output = $this->formatter->formatRequest($request);

        self::assertStringContainsString('Authorization: [REDACTED]', $output);
        self::assertStringNotContainsString('eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9', $output);
        self::assertStringContainsString('Accept: application/json', $output);
    }

    #[Test]
    public function redactsClientSecretInFormBody(): void
    {
        $body = 'grant_type=client_credentials&client_id=myapp&client_secret=s3cret&scope=openid';
        $request = new Request(
            'POST',
            'https://api.example.com/token',
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            $body,
        );

        $output = $this->formatter->formatRequest($request);

        self::assertStringContainsString('client_secret=[REDACTED]', $output);
        self::assertStringNotContainsString('s3cret', $output);
        self::assertStringContainsString('client_id=myapp', $output);
        self::assertStringContainsString('grant_type=client_credentials', $output);
    }

    #[Test]
    public function redactsPasswordInFormBody(): void
    {
        $body = 'grant_type=password&username=user@example.com&password=p4ssw0rd&client_id=myapp';
        $request = new Request(
            'POST',
            'https://api.example.com/token',
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            $body,
        );

        $output = $this->formatter->formatRequest($request);

        self::assertStringContainsString('password=[REDACTED]', $output);
        self::assertStringNotContainsString('p4ssw0rd', $output);
        self::assertStringContainsString('username=user@example.com', $output);
    }

    #[Test]
    public function redactsAccessTokenInJsonResponse(): void
    {
        $responseBody = json_encode([
            'access_token' => 'eyJhbGciOiJSUzI1NiJ9.payload.signature',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], JSON_THROW_ON_ERROR);

        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            $responseBody,
        );

        $output = $this->formatter->formatResponse($response);

        self::assertStringContainsString('"access_token":"[REDACTED]"', $output);
        self::assertStringNotContainsString('eyJhbGciOiJSUzI1NiJ9', $output);
        self::assertStringContainsString('"token_type":"Bearer"', $output);
        self::assertStringContainsString('"expires_in":3600', $output);
    }

    #[Test]
    public function redactsRefreshTokenInJsonResponse(): void
    {
        $responseBody = json_encode([
            'access_token' => 'abc123',
            'refresh_token' => 'refresh-secret-value',
            'token_type' => 'Bearer',
        ], JSON_THROW_ON_ERROR);

        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            $responseBody,
        );

        $output = $this->formatter->formatResponse($response);

        self::assertStringContainsString('"refresh_token":"[REDACTED]"', $output);
        self::assertStringNotContainsString('refresh-secret-value', $output);
    }

    #[Test]
    public function preservesNonSensitiveContent(): void
    {
        $responseBody = json_encode([
            'walletBalance' => 1500,
            'currency' => 'EUR',
        ], JSON_THROW_ON_ERROR);

        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            $responseBody,
        );

        $output = $this->formatter->formatResponse($response);

        self::assertStringContainsString('"walletBalance":1500', $output);
        self::assertStringContainsString('"currency":"EUR"', $output);
    }

    #[Test]
    public function replacesBinaryPdfContentWithSizeIndicator(): void
    {
        $binaryContent = str_repeat("\x00\xFF", 500);
        $response = new Response(
            200,
            ['Content-Type' => 'application/pdf'],
            $binaryContent,
        );

        $output = $this->formatter->formatResponse($response);

        self::assertStringContainsString('[binary content, 1000 bytes]', $output);
        self::assertStringNotContainsString("\x00", $output);
    }

    #[Test]
    public function replacesBinaryPngContentWithSizeIndicator(): void
    {
        $binaryContent = str_repeat("\x89PNG", 100);
        $response = new Response(
            200,
            ['Content-Type' => 'image/png'],
            $binaryContent,
        );

        $output = $this->formatter->formatResponse($response);

        self::assertStringContainsString('[binary content, 400 bytes]', $output);
    }

    #[Test]
    public function replacesBinaryOctetStreamWithSizeIndicator(): void
    {
        $binaryContent = str_repeat("\x00", 2048);
        $response = new Response(
            200,
            ['Content-Type' => 'application/octet-stream'],
            $binaryContent,
        );

        $output = $this->formatter->formatResponse($response);

        self::assertStringContainsString('[binary content, 2048 bytes]', $output);
    }

    #[Test]
    public function truncatesLongBody(): void
    {
        $longBody = str_repeat('a', 2000);
        $response = new Response(
            200,
            ['Content-Type' => 'text/plain'],
            $longBody,
        );

        $output = $this->formatter->formatResponse($response);

        self::assertStringContainsString('... [truncated]', $output);
        // The header portion + 1000 chars of body + truncation marker
        self::assertStringNotContainsString(str_repeat('a', 1001), $output);
    }

    #[Test]
    public function rewindsRequestStreamAfterReading(): void
    {
        $body = 'grant_type=client_credentials&client_id=myapp';
        $request = new Request(
            'POST',
            'https://api.example.com/token',
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            $body,
        );

        $this->formatter->formatRequest($request);

        // Stream should be rewound — reading it again must return the full body
        self::assertSame($body, (string) $request->getBody());
    }

    #[Test]
    public function rewindsResponseStreamAfterReading(): void
    {
        $body = '{"status":"ok"}';
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            $body,
        );

        $this->formatter->formatResponse($response);

        // Stream should be rewound — reading it again must return the full body
        self::assertSame($body, (string) $response->getBody());
    }

    #[Test]
    public function formatsRequestLineCorrectly(): void
    {
        $request = new Request('GET', 'https://api.example.com/info');

        $output = $this->formatter->formatRequest($request);

        self::assertStringContainsString('GET /info HTTP/1.1', $output);
    }

    #[Test]
    public function formatsResponseStatusLineCorrectly(): void
    {
        $response = new Response(200);

        $output = $this->formatter->formatResponse($response);

        self::assertStringContainsString('HTTP/1.1 200 OK', $output);
    }

    #[Test]
    public function preservesNonSensitiveHeaders(): void
    {
        $request = new Request(
            'GET',
            'https://api.example.com/resource',
            [
                'Accept' => 'application/json',
                'User-Agent' => 'deutschepost-sdk/1.0',
                'X-Custom-Header' => 'custom-value',
            ],
        );

        $output = $this->formatter->formatRequest($request);

        self::assertStringContainsString('Accept: application/json', $output);
        self::assertStringContainsString('User-Agent: deutschepost-sdk/1.0', $output);
        self::assertStringContainsString('X-Custom-Header: custom-value', $output);
    }
}
