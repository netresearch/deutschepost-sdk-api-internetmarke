<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\TestCase\Service;

use DeutschePost\Sdk\Internetmarke\Api\Data\UserProfileInterface;
use DeutschePost\Sdk\Internetmarke\Service\ServiceFactory;
use DeutschePost\Sdk\Internetmarke\Test\Provider\Http\Service\UserProfileTestProvider;
use Http\Mock\Client as MockClient;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

/**
 * Integration test for UserProfileService through ServiceFactory.
 *
 * Authenticated service — auth response must be queued first.
 */
class UserProfileServiceTest extends TestCase
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
    public function getProfileReturnsParsedUserProfile(): void
    {
        $mockClient = new MockClient();
        foreach (UserProfileTestProvider::getProfileSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createUserProfileService();
        $profile = $service->getProfile();

        self::assertInstanceOf(UserProfileInterface::class, $profile);
        self::assertSame('6123456789', $profile->getEkp());
        self::assertSame('Deutsche Post AG - TCB', $profile->getCompany());
        self::assertSame('Herr', $profile->getSalutation());
        self::assertSame('Max', $profile->getFirstname());
        self::assertSame('Mustermann', $profile->getLastname());
        self::assertSame('Hauptstrasse', $profile->getStreet());
        self::assertSame('5', $profile->getHouseNo());
        self::assertSame('33602', $profile->getZip());
        self::assertSame('Teststadt', $profile->getCity());
        self::assertSame('DEU', $profile->getCountry());
        self::assertSame('max.mustermann@deutschepost.de', $profile->getMail());
    }

    #[Test]
    public function authTokenIsAcquiredBeforeServiceCall(): void
    {
        $mockClient = new MockClient();
        foreach (UserProfileTestProvider::getProfileSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createUserProfileService();
        $service->getProfile();

        $requests = $mockClient->getRequests();

        // First request: auth (POST /user), second: service (GET /user/profile)
        self::assertCount(2, $requests);
        self::assertSame('POST', $requests[0]->getMethod());
        self::assertStringContainsString('/user', (string) $requests[0]->getUri());
        self::assertStringContainsString(
            'application/x-www-form-urlencoded',
            $requests[0]->getHeaderLine('Content-Type'),
        );

        self::assertSame('GET', $requests[1]->getMethod());
        self::assertStringContainsString('/user/profile', (string) $requests[1]->getUri());
    }

    #[Test]
    public function bearerTokenIsIncludedInServiceRequest(): void
    {
        $mockClient = new MockClient();
        foreach (UserProfileTestProvider::getProfileSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createUserProfileService();
        $service->getProfile();

        $serviceRequest = $mockClient->getRequests()[1];
        $authHeader = $serviceRequest->getHeaderLine('Authorization');

        self::assertStringStartsWith('Bearer ', $authHeader);
        self::assertStringContainsString('BnN6L2SeyMjKcIMGhgaaUO6GNAIMBtdqmG7klJKbcIo=', $authHeader);
    }

    #[Test]
    public function contractValidation(): void
    {
        $mockClient = new MockClient();
        foreach (UserProfileTestProvider::getProfileSuccess() as $response) {
            $mockClient->addResponse($response);
        }

        $service = $this->createFactory($mockClient)->createUserProfileService();
        $service->getProfile();

        $requests = $mockClient->getRequests();

        // Validate auth request against spec
        self::assertRequestMatchesSpec($requests[0]);

        // Validate service request against spec
        self::assertRequestMatchesSpec($requests[1]);
    }
}
