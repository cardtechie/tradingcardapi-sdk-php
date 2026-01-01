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
make check          # Run all quality checks (tests + analysis + format check)
make quality        # Run comprehensive quality checks with coverage
make fix            # Format code and run analysis
make all            # Install, test, analyse, and format
make shell          # Access container shell
make down           # Stop Docker containers
```

### Code Quality Standards
This project maintains high code quality standards with automated checks:

**Static Analysis**: PHPStan Level 4 with strict type checking
**Code Style**: Laravel Pint for PSR-12 compliance
**Testing**: Pest with minimum 80% coverage requirement
**CI/CD**: Automated quality checks on all PRs and pushes

**Pre-commit Setup** (Optional):
```bash
pip install pre-commit
pre-commit install
```

**Quality Commands**:
```bash
make check          # Quick quality check (tests + analysis + format check)
make quality        # Full quality check with coverage
make format-check   # Check code formatting without changes
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

## Important Reminders

**CRITICAL DEVELOPMENT WORKFLOW**: After every code change, you MUST run the following checks:

1. **Format Code**: `make format` (fixes formatting issues automatically)
2. **Check Coverage**: `make test-coverage` (ensures 80%+ coverage is maintained)
3. **Verify Quality**: `make check` (runs tests + analysis + format check)

**Quality Standards Enforcement**:
- All code must pass PHPStan Level 4 analysis with zero errors
- All code must follow PSR-12 formatting standards via Laravel Pint
- Test coverage must remain at 80% or higher
- All tests must pass before considering changes complete

**Quick Quality Commands**:
```bash
make format         # Fix all formatting issues
make test-coverage  # Check test coverage percentage
make check          # Verify all quality standards
```

**Always Update Documentation**: When making changes to the codebase, ensure the README.md is updated to reflect:
- New features or functionality
- Changed API methods or usage patterns  
- Updated requirements or dependencies
- Modified installation or configuration steps
- New development commands or workflows

The README.md is the public face of this repository and should always accurately represent the current state of the project.

## Release Management

**CRITICAL: Packagist Version Management**

When creating releases, follow these rules to prevent Packagist publishing issues:

1. **Never include hardcoded version in composer.json**:
   - ❌ `"version": "0.1.3"` - causes Packagist validation failures and webhook 403 errors
   - ✅ Let git tags define versions automatically

2. **Release Process**:
   ```bash
   # 1. Update CHANGELOG.md with new version details
   # 2. Commit changes (without version in composer.json)
   # 3. Create and push git tag
   git tag -a 0.1.4 -m "v0.1.4 - Feature description"
   git push origin 0.1.4
   # 4. Create GitHub release from the tag
   gh release create 0.1.4 --title "v0.1.4 - Feature description" --generate-notes
   ```

3. **Why this matters**:
   - Hardcoded versions that don't match git tags cause Packagist to reject updates
   - Results in webhook failures (403 errors) and versions not appearing on Packagist
   - Standard practice by major packages (Laravel, Guzzle, Spatie) is to omit version field

**Reference**: [Packagist troubleshooting guide](https://blog.packagist.com/tagged-a-new-release-for-composer-and-it-wont-show-up-on-packagist/)