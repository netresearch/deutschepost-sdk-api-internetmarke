<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Sdk\Internetmarke\Test\Service;

use DeutschePost\Sdk\Internetmarke\Api\Data\UserProfileInterface;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceException;
use DeutschePost\Sdk\Internetmarke\Serializer\JsonSerializer;
use DeutschePost\Sdk\Internetmarke\Service\UserProfileService;
use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserProfileServiceTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    private function createProfileResponse(): \Psr\Http\Message\ResponseInterface
    {
        $body = json_encode([
            'ekp' => '1234567890',
            'company' => 'Musterfirma GmbH',
            'salutation' => 'Herr',
            'title' => 'Dr.',
            'invoiceType' => 'ONLINE',
            'invoiceFrequency' => 'DECADE',
            'mail' => 'max.mustermann@example.com',
            'firstname' => 'Max',
            'lastname' => 'Mustermann',
            'street' => 'Musterstraße',
            'houseNo' => '42',
            'zip' => '12345',
            'city' => 'Musterstadt',
            'country' => 'DEU',
            'phone' => '+49 123 456789',
            'pobox' => '1234',
            'poboxZip' => '12345',
            'poboxCity' => 'Musterstadt',
            'epostbrief' => 'max@epost.de',
        ], JSON_THROW_ON_ERROR);

        return $this->factory->createResponse(200)->withBody($this->factory->createStream($body));
    }

    #[Test]
    public function getProfileReturnsUserProfile(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse($this->createProfileResponse());

        $service = new UserProfileService(
            $mockClient,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );

        $profile = $service->getProfile();

        self::assertInstanceOf(UserProfileInterface::class, $profile);
        self::assertSame('1234567890', $profile->getEkp());
        self::assertSame('Musterfirma GmbH', $profile->getCompany());
        self::assertSame('Herr', $profile->getSalutation());
        self::assertSame('Dr.', $profile->getTitle());
        self::assertSame('ONLINE', $profile->getInvoiceType());
        self::assertSame('DECADE', $profile->getInvoiceFrequency());
        self::assertSame('max.mustermann@example.com', $profile->getMail());
        self::assertSame('Max', $profile->getFirstname());
        self::assertSame('Mustermann', $profile->getLastname());
        self::assertSame('Musterstraße', $profile->getStreet());
        self::assertSame('42', $profile->getHouseNo());
        self::assertSame('12345', $profile->getZip());
        self::assertSame('Musterstadt', $profile->getCity());
        self::assertSame('DEU', $profile->getCountry());
        self::assertSame('+49 123 456789', $profile->getPhone());
        self::assertSame('1234', $profile->getPobox());
        self::assertSame('12345', $profile->getPoboxZip());
        self::assertSame('Musterstadt', $profile->getPoboxCity());
        self::assertSame('max@epost.de', $profile->getEpostbrief());
    }

    #[Test]
    public function sendsGetRequestToCorrectEndpoint(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse($this->createProfileResponse());

        $service = new UserProfileService(
            $mockClient,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );

        $service->getProfile();

        $lastRequest = $mockClient->getLastRequest();
        self::assertSame('GET', $lastRequest->getMethod());
        self::assertSame('https://api.example.com/user/profile', (string) $lastRequest->getUri());
    }

    #[Test]
    public function handlesPartialResponse(): void
    {
        $body = json_encode([
            'mail' => 'minimal@example.com',
            'country' => 'DEU',
        ], JSON_THROW_ON_ERROR);

        $response = $this->factory->createResponse(200)->withBody($this->factory->createStream($body));

        $mockClient = new MockClient();
        $mockClient->addResponse($response);

        $service = new UserProfileService(
            $mockClient,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );

        $profile = $service->getProfile();

        self::assertSame('minimal@example.com', $profile->getMail());
        self::assertSame('DEU', $profile->getCountry());
        self::assertSame('', $profile->getEkp());
        self::assertSame('', $profile->getCompany());
        self::assertSame('', $profile->getFirstname());
        self::assertSame('', $profile->getLastname());
        self::assertSame('', $profile->getPhone());
        self::assertSame('', $profile->getEpostbrief());
    }

    #[Test]
    public function throwsServiceExceptionOnApiError(): void
    {
        $mockClient = new MockClient();
        $mockClient->addException(new \RuntimeException('timeout'));

        $service = new UserProfileService(
            $mockClient,
            $this->factory,
            new JsonSerializer(),
            'https://api.example.com',
        );

        $this->expectException(ServiceException::class);

        $service->getProfile();
    }
}
