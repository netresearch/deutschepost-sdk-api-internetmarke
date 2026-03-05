<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Service;

use DeutschePost\Sdk\Internetmarke\Api\Data\RefundInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\RetoureStateInterface;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceException;
use DeutschePost\Sdk\Internetmarke\Model\RefundRequest;
use DeutschePost\Sdk\Internetmarke\Model\RefundVoucher;
use DeutschePost\Sdk\Internetmarke\Serializer\JsonSerializer;
use DeutschePost\Sdk\Internetmarke\Service\RefundService;
use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RefundServiceTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    #[Test]
    public function requestRefundReturnsRefund(): void
    {
        $responseBody = json_encode([
            'shopRetoureId' => 'R-1234-5678',
            'retoureTransactionId' => 98765,
        ], JSON_THROW_ON_ERROR);

        $mockClient = new MockClient();
        $mockClient->addResponse(
            $this->factory->createResponse(200)->withBody($this->factory->createStream($responseBody))
        );

        $service = new RefundService(
            $mockClient,
            $this->factory,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );

        $request = new RefundRequest('ORD-9876', [
            new RefundVoucher('A001ABCD1234', 'TRK001234'),
            new RefundVoucher('A001ABCD5678', 'TRK005678'),
        ]);

        $refund = $service->requestRefund($request);

        self::assertInstanceOf(RefundInterface::class, $refund);
        self::assertSame('R-1234-5678', $refund->getShopRetoureId());
        self::assertSame('98765', $refund->getRetoureTransactionId());
    }

    #[Test]
    public function sendsCorrectRequestBody(): void
    {
        $responseBody = json_encode([
            'shopRetoureId' => 'R-1',
            'retoureTransactionId' => 1,
        ], JSON_THROW_ON_ERROR);

        $mockClient = new MockClient();
        $mockClient->addResponse(
            $this->factory->createResponse(200)->withBody($this->factory->createStream($responseBody))
        );

        $service = new RefundService(
            $mockClient,
            $this->factory,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );

        $request = new RefundRequest('ORD-42', [
            new RefundVoucher('V1', 'T1'),
        ]);

        $service->requestRefund($request);

        $lastRequest = $mockClient->getLastRequest();
        self::assertSame('POST', $lastRequest->getMethod());
        self::assertStringContainsString('/app/retoure', (string) $lastRequest->getUri());

        $body = json_decode((string) $lastRequest->getBody(), true);
        self::assertSame('ORD-42', $body['shoppingCart']['shopOrderId']);
        self::assertCount(1, $body['shoppingCart']['voucherList']);
        self::assertSame('V1', $body['shoppingCart']['voucherList'][0]['voucherId']);
        self::assertSame('T1', $body['shoppingCart']['voucherList'][0]['trackId']);
    }

    #[Test]
    public function throwsServiceExceptionOnApiError(): void
    {
        $mockClient = new MockClient();
        $mockClient->addException(new \RuntimeException('timeout'));

        $service = new RefundService(
            $mockClient,
            $this->factory,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );

        $request = new RefundRequest('ORD-1', [new RefundVoucher('V1', 'T1')]);

        $this->expectException(ServiceException::class);

        $service->requestRefund($request);
    }

    #[Test]
    public function sendsFullOrderRefundWithShopOrderIdOnly(): void
    {
        $responseBody = json_encode([
            'shopRetoureId' => 'R-1',
            'retoureTransactionId' => 1,
        ], JSON_THROW_ON_ERROR);

        $mockClient = new MockClient();
        $mockClient->addResponse(
            $this->factory->createResponse(200)->withBody($this->factory->createStream($responseBody))
        );

        $service = new RefundService(
            $mockClient,
            $this->factory,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );

        $request = new RefundRequest('ORD-42');
        $service->requestRefund($request);

        $body = json_decode((string) $mockClient->getLastRequest()->getBody(), true);
        self::assertSame('ORD-42', $body['shoppingCart']['shopOrderId']);
        self::assertArrayNotHasKey('voucherList', $body['shoppingCart']);
    }

    #[Test]
    public function sendsVoucherOnlyRefundWithoutShopOrderId(): void
    {
        $responseBody = json_encode([
            'shopRetoureId' => 'R-2',
            'retoureTransactionId' => 2,
        ], JSON_THROW_ON_ERROR);

        $mockClient = new MockClient();
        $mockClient->addResponse(
            $this->factory->createResponse(200)->withBody($this->factory->createStream($responseBody))
        );

        $service = new RefundService(
            $mockClient,
            $this->factory,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );

        $request = new RefundRequest(vouchers: [
            new RefundVoucher('V1', 'T1'),
            new RefundVoucher('V2', 'T2'),
        ]);
        $service->requestRefund($request);

        $body = json_decode((string) $mockClient->getLastRequest()->getBody(), true);
        self::assertArrayNotHasKey('shopOrderId', $body['shoppingCart']);
        self::assertCount(2, $body['shoppingCart']['voucherList']);
    }

    #[Test]
    public function throwsExceptionWhenBothParamsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RefundRequest();
    }

    #[Test]
    public function getRetoureStateReturnsStateObjects(): void
    {
        $responseBody = json_encode([
            'RetrieveRetoureStateResponse' => [
                [
                    'retoureTransactionId' => 12345,
                    'shopRetoureId' => 'R-9876',
                    'totalCount' => 3,
                    'countStillOpen' => 1,
                    'retourePrice' => 255,
                    'creationDate' => '2024-04-03T08:37:17Z',
                    'serialnumber' => 'ABC1234567',
                    'refundedVouchers' => [
                        ['voucherId' => 'V-001', 'trackId' => 'TRK-001'],
                        ['voucherId' => 'V-002', 'trackId' => null],
                    ],
                    'notRefundedVouchers' => [
                        ['voucherId' => 'V-003', 'trackId' => 'TRK-003'],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $mockClient = new MockClient();
        $mockClient->addResponse(
            $this->factory->createResponse(200)->withBody($this->factory->createStream($responseBody))
        );

        $service = new RefundService(
            $mockClient,
            $this->factory,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );

        $states = $service->getRetoureState();

        self::assertCount(1, $states);
        self::assertContainsOnlyInstancesOf(RetoureStateInterface::class, $states);

        $state = $states[0];
        self::assertSame('12345', $state->getRetoureTransactionId());
        self::assertSame('R-9876', $state->getShopRetoureId());
        self::assertSame(3, $state->getTotalCount());
        self::assertSame(1, $state->getCountStillOpen());
        self::assertSame(255, $state->getRetourePrice());
        self::assertInstanceOf(\DateTimeImmutable::class, $state->getCreationDate());
        self::assertSame('2024-04-03T08:37:17+00:00', $state->getCreationDate()->format('Y-m-d\TH:i:sP'));
        self::assertSame('ABC1234567', $state->getSerialnumber());

        self::assertCount(2, $state->getRefundedVouchers());
        self::assertSame('V-001', $state->getRefundedVouchers()[0]->getVoucherId());
        self::assertSame('TRK-001', $state->getRefundedVouchers()[0]->getTrackId());
        self::assertSame('V-002', $state->getRefundedVouchers()[1]->getVoucherId());
        self::assertNull($state->getRefundedVouchers()[1]->getTrackId());

        self::assertCount(1, $state->getNotRefundedVouchers());
        self::assertSame('V-003', $state->getNotRefundedVouchers()[0]->getVoucherId());
    }

    #[Test]
    public function getRetoureStateSendsQueryParameters(): void
    {
        $responseBody = json_encode([
            'RetrieveRetoureStateResponse' => [],
        ], JSON_THROW_ON_ERROR);

        $mockClient = new MockClient();
        $mockClient->addResponse(
            $this->factory->createResponse(200)->withBody($this->factory->createStream($responseBody))
        );

        $service = new RefundService(
            $mockClient,
            $this->factory,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );

        $service->getRetoureState(
            shopRetoureId: 'R-42',
            retoureTransactionId: 99,
            startDate: new \DateTimeImmutable('2024-01-01T00:00:00+00:00'),
            endDate: new \DateTimeImmutable('2024-12-31T23:59:59+00:00'),
        );

        $lastRequest = $mockClient->getLastRequest();
        self::assertSame('GET', $lastRequest->getMethod());

        $uri = (string) $lastRequest->getUri();
        self::assertStringContainsString('/app/retoure', $uri);
        self::assertStringContainsString('shopRetoureId=R-42', $uri);
        self::assertStringContainsString('retoureTransactionId=99', $uri);
        self::assertStringContainsString('startDate=2024-01-01T00', $uri);
        self::assertStringContainsString('endDate=2024-12-31T23', $uri);
    }

    #[Test]
    public function getRetoureStateSendsNoParamsWhenNoneGiven(): void
    {
        $responseBody = json_encode([
            'RetrieveRetoureStateResponse' => [],
        ], JSON_THROW_ON_ERROR);

        $mockClient = new MockClient();
        $mockClient->addResponse(
            $this->factory->createResponse(200)->withBody($this->factory->createStream($responseBody))
        );

        $service = new RefundService(
            $mockClient,
            $this->factory,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );

        $service->getRetoureState();

        $lastRequest = $mockClient->getLastRequest();
        $uri = (string) $lastRequest->getUri();
        self::assertSame('https://api.example.com/app/retoure', $uri);
    }

    #[Test]
    public function getRetoureStateReturnsEmptyArrayWhenNoTransactions(): void
    {
        $responseBody = json_encode([
            'RetrieveRetoureStateResponse' => [],
        ], JSON_THROW_ON_ERROR);

        $mockClient = new MockClient();
        $mockClient->addResponse(
            $this->factory->createResponse(200)->withBody($this->factory->createStream($responseBody))
        );

        $service = new RefundService(
            $mockClient,
            $this->factory,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );

        self::assertSame([], $service->getRetoureState());
    }

    #[Test]
    public function getRetoureStateThrowsServiceExceptionOnError(): void
    {
        $mockClient = new MockClient();
        $mockClient->addException(new \RuntimeException('connection refused'));

        $service = new RefundService(
            $mockClient,
            $this->factory,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );

        $this->expectException(ServiceException::class);

        $service->getRetoureState();
    }
}
