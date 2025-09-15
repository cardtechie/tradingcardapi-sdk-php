# Trading Card API SDK for PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cardtechie/tradingcardapi-sdk-php.svg?style=flat-square)](https://packagist.org/packages/cardtechie/tradingcardapi-sdk-php)
[![GitHub Tests](https://img.shields.io/github/actions/workflow/status/cardtechie/tradingcardapi-sdk-php/code-quality.yml?branch=main&label=tests&style=flat-square)](https://github.com/cardtechie/tradingcardapi-sdk-php/actions?query=workflow%3Acode-quality+branch%3Amain)
[![PHPStan](https://img.shields.io/github/actions/workflow/status/cardtechie/tradingcardapi-sdk-php/code-quality.yml?branch=main&label=phpstan&style=flat-square)](https://github.com/cardtechie/tradingcardapi-sdk-php/actions)
[![Total Downloads](https://img.shields.io/packagist/dt/cardtechie/tradingcardapi-sdk-php.svg?style=flat-square)](https://packagist.org/packages/cardtechie/tradingcardapi-sdk-php)

A modern PHP SDK for integrating with the Trading Card API. This Laravel package provides a clean, type-safe interface for accessing trading card data including cards, sets, players, teams, and more.

## ✨ Features

- 🔧 **Laravel Integration** - Built specifically for Laravel applications
- 🛡️ **Type Safety** - Full PHPStan Level 4 compliance with strict typing
- 🧪 **Well Tested** - Comprehensive test suite with 80%+ coverage
- 📦 **Easy Installation** - Simple Composer installation and configuration
- 🔄 **OAuth2 Authentication** - Automatic token management and renewal
- 🚨 **Enhanced Error Handling** - Specific exception classes for different error types
- 📖 **Rich Documentation** - Clear examples and comprehensive API coverage
- ⚡ **High Performance** - Efficient HTTP client with connection pooling

## 📋 Requirements

- PHP 8.1 or higher
- Laravel 10.0 or higher
- GuzzleHTTP 7.5 or higher

## 🚀 Installation

Install the package via Composer:

```bash
composer require cardtechie/tradingcardapi-sdk-php
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="tradingcardapi-config"
```

Add your API credentials to your `.env` file:

```env
TRADINGCARDAPI_URL=https://api.tradingcardapi.com
TRADINGCARDAPI_CLIENT_ID=your_client_id
TRADINGCARDAPI_CLIENT_SECRET=your_client_secret
TRADINGCARDAPI_SSL_VERIFY=true
```

## 🎯 Quick Start

### Using the Facade

```php
use CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk;

// Get a specific card
$card = TradingCardApiSdk::card()->get('card-id');

// Search for cards
$cards = TradingCardApiSdk::card()->getList(['name' => 'Pikachu']);

// Get player information
$player = TradingCardApiSdk::player()->get('player-id');
```

### Using the Helper Function

```php
// Get a set with related data
$set = tradingcardapi()->set()->get('set-id', ['include' => 'cards,genre']);

// Create a new team
$team = tradingcardapi()->team()->create([
    'name' => 'New Team',
    'location' => 'City Name'
]);
```

### Error Handling

The SDK provides comprehensive error handling with specific exception classes:

```php
use CardTechie\TradingCardApiSdk\Exceptions\{
    CardNotFoundException,
    ValidationException,
    RateLimitException,
    AuthenticationException
};

try {
    $card = TradingCardApiSdk::card()->get('invalid-id');
} catch (CardNotFoundException $e) {
    // Handle missing card
    echo "Card not found: " . $e->getMessage();
} catch (ValidationException $e) {
    // Handle validation errors
    foreach ($e->getValidationErrors() as $field => $errors) {
        echo "Field $field: " . implode(', ', $errors);
    }
} catch (RateLimitException $e) {
    // Handle rate limiting
    echo "Rate limited. Retry after: " . $e->getRetryAfter() . " seconds";
}
```

### Direct Class Usage

```php
use CardTechie\TradingCardApiSdk\TradingCardApi;

$api = new TradingCardApi();
$genres = $api->genre()->getList();
```

## 📚 Available Resources

The SDK provides access to the following Trading Card API resources:

| Resource | Description | Methods |
|----------|-------------|---------|
| **Cards** | Individual trading cards | `get()`, `create()`, `update()`, `delete()` |
| **Sets** | Card sets and collections | `get()`, `list()`, `create()`, `update()`, `delete()`, `checklist($id)`, `addMissingCards($id)`, `addChecklist($request, $id)` |
| **Players** | Player information | `get()`, `getList()`, `create()` |
| **Teams** | Team data | `get()`, `getList()`, `create()` |
| **Genres** | Card categories/types | `get()`, `list()`, `create()`, `update()`, `delete()`, `listDeleted()`, `deleted($id)` |
| **Brands** | Trading card brands | `get()`, `list()`, `create()`, `update()`, `delete()` |
| **Manufacturers** | Trading card manufacturers | `get()`, `list()`, `create()`, `update()`, `delete()` |
| **Years** | Trading card years | `get()`, `list()`, `create()`, `update()`, `delete()` |
| **ObjectAttributes** | Object attributes | `get()`, `list()`, `create()`, `update()`, `delete()` |
| **Stats** | Model statistics | `get($type)` |
| **Attributes** | Card attributes | `get()`, `getList()` |

## 🔧 Configuration

The configuration file (`config/tradingcardapi.php`) supports:

```php
return [
    'url' => env('TRADINGCARDAPI_URL', ''),
    'ssl_verify' => (bool) env('TRADINGCARDAPI_SSL_VERIFY', true),
    'client_id' => env('TRADINGCARDAPI_CLIENT_ID', ''),
    'client_secret' => env('TRADINGCARDAPI_CLIENT_SECRET', ''),
];
```

## 🧪 Development & Testing

This project uses modern PHP development tools and practices:

### Prerequisites

- Docker and Docker Compose
- Make (optional, for convenience commands)

### Getting Started

```bash
# Clone the repository
git clone https://github.com/cardtechie/tradingcardapi-sdk-php.git
cd tradingcardapi-sdk-php

# Start development environment
make up

# Install dependencies
make install

# Run tests
make test

# Run code quality checks
make check
```

### Available Commands

```bash
make test              # Run test suite
make analyse           # Run PHPStan static analysis
make format            # Format code with Laravel Pint
make check             # Run all quality checks
make quality           # Run comprehensive quality checks with coverage
make ci                # Run CI pipeline locally

# Release management commands
make version           # Show current version
make changelog-update  # Update changelog for current version
make release-notes-preview  # Generate release notes preview
```

### Code Quality Standards

This project maintains high code quality standards:

- ✅ **PHPStan Level 4** - Strict static analysis
- ✅ **PSR-12** - Code style compliance via Laravel Pint
- ✅ **80%+ Test Coverage** - Comprehensive test suite using Pest
- ✅ **Automated CI/CD** - Quality checks on all PRs

## 📖 Documentation

- **[Error Handling Guide](docs/ERROR-HANDLING.md)** - Comprehensive guide to exception handling
- **[Response Validation](docs/VALIDATION.md)** - Response validation and schema handling  
- **[Version Management](docs/VERSION-MANAGEMENT.md)** - Release process and versioning
- **[Trading Card API Documentation](https://docs.tradingcardapi.com)** - Complete API reference

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

### Development Workflow

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run quality checks: `make check`
5. Submit a pull request

## 🐛 Bug Reports & Feature Requests

Please use the [GitHub Issues](https://github.com/cardtechie/tradingcardapi-sdk-php/issues) to report bugs or request features.

## 🔒 Security

Please review our [Security Policy](../../security/policy) for reporting security vulnerabilities.

## 🚀 Release Process

This project uses a sophisticated, automated release management system adapted from the main Trading Card API repository.

### Version Management

The SDK uses intelligent, branch-aware semantic versioning:

- **Production releases** (`1.2.3`) - Created from `main` branch
- **Beta releases** (`1.3.0.beta-5`) - Created from `develop` branch  
- **Release candidates** (`1.3.0.rc-2`) - Created from `release/*` branches
- **Development versions** (`1.2.3-alpha.4`) - Feature branches

### Development Commands

```bash
# Check current version
make version

# Preview version for different branches
make version-preview --branch=main
make version-preview --branch=develop

# Update changelog for current version
make changelog-update

# Generate release notes preview
make release-notes-preview
```

### Automated Release Process

1. **Development**: Features are developed on feature branches
2. **Integration**: Changes are merged to `develop` for testing
3. **Release Preparation**: Release branches are created for final testing
4. **Production Release**: Stable releases are merged to `main`
5. **Automation**: GitHub Actions handles versioning, changelog updates, and Packagist publishing

See [docs/VERSION-MANAGEMENT.md](docs/VERSION-MANAGEMENT.md) for complete release process documentation.

## 📄 Changelog

See [CHANGELOG.md](CHANGELOG.md) for recent changes.

## 👥 Credits

- [Josh Harrison](https://github.com/picklewagon) - Lead Developer
- [All Contributors](../../contributors)

## 📜 License

This project is licensed under the MIT License. See [LICENSE.md](LICENSE.md) for details.

---

<p align="center">
Made with ❤️ by <a href="https://github.com/cardtechie">CardTechie</a>
</p>