<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\TestCase\Service;

use DeutschePost\Sdk\Internetmarke\Api\Data\RefundInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\RetoureStateInterface;
use DeutschePost\Sdk\Internetmarke\Model\RefundRequest;
use DeutschePost\Sdk\Internetmarke\Model\RefundVoucher;
use DeutschePost\Sdk\Internetmarke\Service\ServiceFactory;
use DeutschePost\Sdk\Internetmarke\Test\Provider\Http\Service\RefundTestProvider;
use Http\Mock\Client as MockClient;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

/**
 * Integration test for RefundService through ServiceFactory.
 *
 * Authenticated service — auth response queued first.
 */
class RefundServiceTest extends TestCase
{
    use ValidatesAgainstOpenApiSpec;

    private function createFactory(MockClient $client): ServiceFactory
    {
        return new ServiceFactory(
            'test-client-id',
            'test-client-secret',
            'test-user',
            'test-pass',
            new TestLogger(),
            $client,
        );
    }

    #[Test]
    public function requestRefundReturnsParsedRefund(): void
    {
        $mockClient = new MockClient();
        foreach (RefundTestProvider::requestRefundSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createRefundService();
        $request = new RefundRequest('98276337');
        $refund = $service->requestRefund($request);

        self::assertInstanceOf(RefundInterface::class, $refund);
        self::assertSame('17288644', $refund->getShopRetoureId());
        self::assertSame('15242129', $refund->getRetoureTransactionId());
    }

    #[Test]
    public function requestRefundSendsPostWithBody(): void
    {
        $mockClient = new MockClient();
        foreach (RefundTestProvider::requestRefundSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createRefundService();
        $request = new RefundRequest(
            '98276337',
            [new RefundVoucher('AF188FFFFF0000004F0B', 'TRK001')],
        );
        $service->requestRefund($request);

        $requests = $mockClient->getRequests();
        self::assertCount(2, $requests);

        $refundRequest = $requests[1];
        self::assertSame('POST', $refundRequest->getMethod());
        self::assertStringContainsString('/app/retoure', (string) $refundRequest->getUri());

        $body = json_decode((string) $refundRequest->getBody(), true);
        self::assertArrayHasKey('shoppingCart', $body);
        self::assertSame('98276337', $body['shoppingCart']['shopOrderId']);
    }

    #[Test]
    public function getRetoureStateReturnsParsedStates(): void
    {
        $mockClient = new MockClient();
        foreach (RefundTestProvider::retoureStateSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createRefundService();
        $states = $service->getRetoureState(shopRetoureId: '17288644');

        self::assertCount(1, $states);
        self::assertContainsOnlyInstancesOf(RetoureStateInterface::class, $states);

        $state = $states[0];
        self::assertSame('15242129', $state->getRetoureTransactionId());
        self::assertSame('17288644', $state->getShopRetoureId());
        self::assertSame(1, $state->getTotalCount());
        self::assertSame(0, $state->getCountStillOpen());
        self::assertSame(95, $state->getRetourePrice());

        // Fixture uses non-standard format "6102025-190339" = 6th October 2025, 19:03:39
        $creationDate = $state->getCreationDate();
        self::assertInstanceOf(\DateTimeImmutable::class, $creationDate);
        self::assertSame('2025-10-06T19:03:39', $creationDate->format('Y-m-d\TH:i:s'));

        self::assertCount(1, $state->getRefundedVouchers());
        self::assertCount(1, $state->getNotRefundedVouchers());
    }

    #[Test]
    public function getRetoureStateSendsGetWithQueryParams(): void
    {
        $mockClient = new MockClient();
        foreach (RefundTestProvider::retoureStateSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createRefundService();
        $service->getRetoureState(shopRetoureId: '17288644');

        $requests = $mockClient->getRequests();
        self::assertCount(2, $requests);

        $stateRequest = $requests[1];
        self::assertSame('GET', $stateRequest->getMethod());
        $uri = (string) $stateRequest->getUri();
        self::assertStringContainsString('/app/retoure', $uri);
        self::assertStringContainsString('shopRetoureId=17288644', $uri);
    }

    #[Test]
    public function contractValidation(): void
    {
        $mockClient = new MockClient();
        foreach (RefundTestProvider::requestRefundSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createRefundService();
        $request = new RefundRequest('98276337');
        $service->requestRefund($request);

        $requests = $mockClient->getRequests();
        self::assertRequestMatchesSpec($requests[0]); // auth
    }
}
