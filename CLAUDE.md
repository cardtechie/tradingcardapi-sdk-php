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

All commands should be run using the provided Makefile, which handles Docker container execution:

### Testing
```bash
make test           # Run all tests using Pest
make test-coverage  # Run tests with coverage report
make pest           # Run Pest directly
```

### Code Quality
```bash
make analyse        # Run PHPStan static analysis
make format         # Format code using Laravel Pint
make phpstan        # Run PHPStan directly
make pint           # Run Laravel Pint directly
```

### Development Workflow
```bash
make up             # Start Docker containers
make install        # Install composer dependencies
make ci             # Run tests and analysis (CI tasks)
make fix            # Format code and run analysis
make all            # Install, test, analyse, and format
make shell          # Access container shell
make down           # Stop Docker containers
```

### Package Development
```bash
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