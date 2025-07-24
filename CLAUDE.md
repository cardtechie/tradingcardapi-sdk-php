# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Architecture Overview

This is a Laravel package providing a PHP SDK for the Trading Card API. The package follows standard Laravel package conventions using Spatie's Laravel Package Tools.

### Core Components

- **TradingCardApi**: Main service class that provides access to all API resources through method calls (e.g., `card()`, `player()`, `set()`)
- **Resources**: Individual API resource classes in `src/Resources/` that handle specific endpoints (Card, Player, Set, Team, etc.)
- **Models**: Eloquent models in `src/Models/` representing API entities
- **ApiRequest Trait**: Shared functionality for HTTP requests, OAuth token management, and API communication

### Key Files

- `src/TradingCardApi.php`: Main service class that acts as entry point to all resources
- `src/TradingCardApiServiceProvider.php`: Laravel service provider for package registration
- `src/Resources/Traits/ApiRequest.php`: Core HTTP client functionality with OAuth authentication
- `config/tradingcardapi.php`: Configuration file for API credentials and settings

## Common Commands

### Testing
Tests should be run within the Docker container to ensure proper PHP environment:
```bash
docker-compose exec app composer test                 # Run all tests using Pest
docker-compose exec app composer test-coverage        # Run tests with coverage report
docker-compose exec app vendor/bin/pest              # Direct Pest command
```

### Code Quality
Code quality tools should also be run within the Docker container:
```bash
docker-compose exec app composer analyse             # Run PHPStan static analysis
docker-compose exec app composer format              # Format code using Laravel Pint
docker-compose exec app vendor/bin/phpstan analyse   # Direct PHPStan command
docker-compose exec app vendor/bin/pint              # Direct Pint command
```

### Package Development
```bash
composer install                                              # Install dependencies
php artisan vendor:publish --tag="tradingcardapi-config"     # Publish config file
php artisan vendor:publish --tag="tradingcardapi-sdk-migrations" # Publish migrations
php artisan vendor:publish --tag="tradingcardapi-sdk-views"  # Publish views
```

## Configuration

The package uses OAuth client credentials for API authentication. Configuration is stored in `config/tradingcardapi.php` and should include:
- API base URL
- Client ID and secret for OAuth
- SSL verification settings

## Usage Patterns

The SDK provides two main usage patterns:
- Facade: `TradingCardApi::card()->get($id)`
- Helper function: `tradingcardapi()->card()->get($id)`

All API resources follow the same pattern of being accessed through the main `TradingCardApi` class and using the shared `ApiRequest` trait for HTTP communication.