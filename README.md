# Trading Card API SDK (PHP)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cardtechie/tradingcardapi-sdk-php.svg?style=flat-square)](https://packagist.org/packages/cardtechie/tradingcardapi-sdk-php)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/cardtechie/tradingcardapi-sdk-php/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/cardtechie/tradingcardapi-sdk-php/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/cardtechie/tradingcardapi-sdk-php/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/cardtechie/tradingcardapi-sdk-php/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/cardtechie/tradingcardapi-sdk-php.svg?style=flat-square)](https://packagist.org/packages/cardtechie/tradingcardapi-sdk-php)

Connect to the Trading Card API to retrieve trading card information to use in your PHP application.

## Installation

Install the package via composer:

```bash
composer require cardtechie/tradingcardapi-sdk-php
```

Publish the config file:

```bash
php artisan vendor:publish --tag="tradingcardapi-config"
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="tradingcardapi-sdk-migrations"
php artisan migrate
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="tradingcardapi-sdk-views"
```

## Usage

```php
$tradingCardApiSdk = new CardTechie\TradingCardApiSdk();
echo $tradingCardApiSdk->echoPhrase('Hello, CardTechie!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Josh Harrison](https://github.com/picklewagon)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
