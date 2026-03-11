# Deutsche Post Internetmarke REST API SDK

The Deutsche Post Internetmarke REST API SDK package offers an interface to the following web services:

- [Deutsche Post Internetmarke REST API v1](https://developer.dhl.com/api-reference/deutsche-post-internetmarke)

## Requirements

### System Requirements

- PHP 8.3+ with JSON extension

### Package Requirements

- `netresearch/jsonmapper`: Mapper for deserialization of JSON response messages into PHP objects
- `php-http/client-common`: HTTPlug PluginClient for composable HTTP middleware
- `php-http/discovery`: Discovery service for HTTP client and message factory implementations
- `php-http/httplug`: Pluggable HTTP client abstraction
- `php-http/logger-plugin`: HTTP client logger plugin for HTTPlug
- `php-http/message`: HTTP message utilities and formatters
- `psr/http-client`: PSR-18 HTTP client interfaces
- `psr/http-factory`: PSR-17 HTTP message factory interfaces
- `psr/http-message`: PSR-7 HTTP message interfaces
- `psr/log`: PSR-3 logger interfaces

### Virtual Package Requirements

- `psr/http-client-implementation`: Any package that provides a PSR-18 compatible HTTP client
- `psr/http-factory-implementation`: Any package that provides PSR-17 compatible HTTP message factories
- `psr/http-message-implementation`: Any package that provides PSR-7 HTTP messages

### Development Package Requirements

- `fig/log-test`: PSR-3 logger implementation for testing purposes
- `nyholm/psr7`: PSR-7 HTTP message factory & message implementation
- `php-http/mock-client`: HTTPlug mock client implementation
- `phpunit/phpunit`: Testing framework
- `phpstan/phpstan`: Static analysis tool
- `rector/rector`: Automatic refactoring tool to help with PHP upgrades
- `squizlabs/php_codesniffer`: Static analysis tool

## Installation

```bash
$ composer require deutschepost/sdk-api-internetmarke
```

## Uninstallation

```bash
$ composer remove deutschepost/sdk-api-internetmarke
```

## Testing

```bash
$ ./vendor/bin/phpunit -c test/phpunit.xml
```

## Features

The Deutsche Post Internetmarke REST API SDK supports the following features:

* Retrieve API Info
* Retrieve Catalog (Page Formats, Contract Products, Motif Images)
* Create Voucher Orders (PDF and PNG)
* Request Refunds
* Retrieve User Profile
* Charge Portokasse Wallet

### Authentication

The Internetmarke REST API requires OAuth2 client credentials combined with
user credentials (see [API Documentation](https://developer.dhl.com/api-reference/deutsche-post-internetmarke)):

1. The **application** is identified by a _Client ID_ and _Client Secret_ obtained
   from the [DHL API Developer Portal](https://developer.dhl.com/user/apps).
2. The **user** is identified by _username_ and _password_ configured in the
   [Deutsche Post Portokasse](https://portokasse.deutschepost.de/).

These credentials are passed to the SDK via the `ServiceFactory` constructor:

```php
$serviceFactory = new \DeutschePost\Sdk\Internetmarke\Service\ServiceFactory(
    clientId: 'your-client-id',
    clientSecret: 'your-client-secret',
    username: 'portokasse-user',
    password: 'portokasse-password',
);
```

### Retrieve Catalog

Retrieve available page formats and contract products.

#### Public API

The library's components suitable for consumption comprise

* services:
  * service factory
  * catalog service
* data transfer objects:
  * page format (id, name, dimensions, voucher grid)
  * contract product (product code, price)
  * catalog item (motif images)

#### Usage

```php
$logger = new \Psr\Log\NullLogger();

$serviceFactory = new \DeutschePost\Sdk\Internetmarke\Service\ServiceFactory(
    clientId: 'your-client-id',
    clientSecret: 'your-client-secret',
    username: 'portokasse-user',
    password: 'portokasse-password',
    logger: $logger,
);

$catalogService = $serviceFactory->createCatalogService();

$pageFormats = $catalogService->getPageFormats();
$contractProducts = $catalogService->getContractProducts();
```

### Create Voucher Order

Create voucher orders with PDF labels. Each order consists of shopping cart
positions built via the `ShoppingCartPositionBuilder`.

#### Public API

The library's components suitable for consumption comprise

* services:
  * service factory
  * order service
  * shopping cart position builder
* data transfer objects:
  * order request
  * order (shopOrderId, walletBalance, vouchers, label PDF)
  * voucher (voucherId, trackId)
* enums:
  * `VoucherLayout` (AddressZone, FrankingZone)
  * `Dpi` (Dpi300, Dpi600, Dpi910)
  * `ShippingList` (None, Xml, Pdf)

#### Usage

```php
$logger = new \Psr\Log\NullLogger();

$serviceFactory = new \DeutschePost\Sdk\Internetmarke\Service\ServiceFactory(
    clientId: 'your-client-id',
    clientSecret: 'your-client-secret',
    username: 'portokasse-user',
    password: 'portokasse-password',
    logger: $logger,
);

$orderService = $serviceFactory->createOrderService();

$builder = \DeutschePost\Sdk\Internetmarke\Model\ShoppingCartPositionBuilder::forPageFormat(
    pageFormatId: 1,
    columns: 2,
    rows: 5,
);

$builder->setItemDetails(productCode: 10001, price: 85);
$builder->setVoucherLayout(\DeutschePost\Sdk\Internetmarke\Api\VoucherLayout::AddressZone);
$builder->setSenderAddress(
    name: 'Sender Company',
    addressLine1: 'Senderstraße 1',
    postalCode: '04317',
    city: 'Leipzig',
    country: 'DEU',
);
$builder->setRecipientAddress(
    name: 'Jane Doe',
    addressLine1: 'Empfängerweg 2',
    postalCode: '53113',
    city: 'Bonn',
    country: 'DEU',
);

$position = $builder->create();

$orderRequest = new \DeutschePost\Sdk\Internetmarke\Model\OrderRequest(
    positions: [$position],
    total: $builder->getTotalAmount(),
    pageFormatId: 1,
);

$order = $orderService->createOrder($orderRequest);

$labelPdf = $order->getLabel();
$vouchers = $order->getVouchers();
```

### Request Refund

Refund previously purchased vouchers.

#### Public API

The library's components suitable for consumption comprise

* services:
  * service factory
  * refund service
* data transfer objects:
  * refund request
  * refund voucher (voucherId, trackId)
  * refund (retoureTransactionId)
  * retoure state

#### Usage

```php
$logger = new \Psr\Log\NullLogger();

$serviceFactory = new \DeutschePost\Sdk\Internetmarke\Service\ServiceFactory(
    clientId: 'your-client-id',
    clientSecret: 'your-client-secret',
    username: 'portokasse-user',
    password: 'portokasse-password',
    logger: $logger,
);

$refundService = $serviceFactory->createRefundService();

$refundRequest = new \DeutschePost\Sdk\Internetmarke\Model\RefundRequest(
    vouchers: [
        new \DeutschePost\Sdk\Internetmarke\Model\RefundVoucher(voucherId: 'A0011234ABC'),
    ],
);

$refund = $refundService->requestRefund($refundRequest);
```
