# Changelog

All notable changes to `Trading Card API PHP SDK` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Complete Team resource CRUD operations (get, update, delete, list, listDeleted, deleted)

### Fixed
- API pagination handling when meta property is missing

## [0.1.4] - 2025-09-27

### Fixed

- **Packagist Publishing Issues** - Removed hardcoded version from composer.json to prevent webhook failures
  - Eliminates 403 errors when publishing to Packagist
  - Follows industry standard practice used by Laravel, Guzzle, and Spatie packages
  - Ensures automatic version detection through git tags

### Added

- **Release Management Documentation** - Comprehensive guide for future releases
  - Documents proper release process to prevent Packagist issues
  - Explains why hardcoded versions cause publishing problems
  - Provides step-by-step release workflow

## [0.1.3] - 2025-09-27

### Added

- **Complete Player Resource Support** - Full CRUD operations for Player entities
  - `TradingCardApiSdk::player()->get($id)` - Get player by ID
  - `TradingCardApiSdk::player()->list($params)` - List players with pagination
  - `TradingCardApiSdk::player()->create($data)` - Create new players
  - `TradingCardApiSdk::player()->update($id, $data)` - Update existing players  
  - `TradingCardApiSdk::player()->delete($id)` - Delete players
  - `TradingCardApiSdk::player()->listDeleted()` - List deleted players
  - `TradingCardApiSdk::player()->deleted($id)` - Get deleted player by ID

- **Player Model Relationships** - Access related data through Player models
  - `$player->getParent()` - Get parent player (for aliases)
  - `$player->getAliases()` - Get all alias players
  - `$player->getTeams()` - Get associated teams
  - `$player->getPlayerteams()` - Get playerteam relationships
  - `$player->getCards()` - Get all cards featuring this player
  - `$player->isAlias()` - Check if player is an alias
  - `$player->hasAliases()` - Check if player has aliases

- **Enhanced Player Model Attributes**
  - `$player->full_name` - Automatically formatted full name
  - `$player->last_name_first` - Last name first format for display

### Enhanced

- **Improved Response Validation** - Better handling of API responses and error detection
- **Enhanced Error Handling** - Graceful fallbacks when API calls fail

## [0.1.2] - 2025-09-21

### Changed

- Replaced automated changelog updates with manual PR validation requirement

## [0.1.1] - 2025-09-21

### Added

- Added missing `list()` method to Card resource for paginated card listings

## [0.1.0] - 2025-09-15

### Added

- Initial stable release of Trading Card API PHP SDK
- Complete SDK implementation with all core Trading Card API endpoints
- Enhanced error handling with specific exception classes
  - Base TradingCardApiException with common properties and methods
  - AuthenticationException for 401 authentication failures
  - AuthorizationException for 403 permission errors
  - ValidationException for 422 validation errors with field-level details
  - RateLimitException for 429 rate limiting with timing information
  - ResourceNotFoundException with specific subclasses (CardNotFoundException, PlayerNotFoundException, SetNotFoundException)
  - NetworkException for connection and network-related errors
  - ServerException for 5xx server errors
- ErrorResponseParser service for intelligent error response parsing
- Comprehensive test coverage for all exception scenarios
- Full PHPStan compliance and Laravel Pint code styling
- Support for Laravel 10.x, 11.x, and 12.x (actively maintained versions)
- Complete API resource implementations (Cards, Players, Sets, Teams, etc.)
- Response validation and schema handling
- Professional documentation and error handling examples
- Support for PHP 8.1, 8.2, 8.3, and 8.4
- Comprehensive GitHub Actions workflows for CI/CD
- Automated testing across multiple PHP and Laravel versions

### Changed

- Updated build system to match API repository's sophisticated process
- Enhanced version management with PHP/Composer integration
- Improved release automation and documentation generation
- Removed Laravel 9.x support (end of life February 2024)
- Updated GitHub Actions to latest compatible versions

### Fixed

- Version script compatibility issues with no git tags scenario
- Changelog formatting and markdown linting compliance
- Code quality and styling issues for production readiness
- Test matrix compatibility issues with Laravel 11+ and prefer-lowest strategy
- PHPStan static analysis errors in ErrorResponseParser

[Unreleased]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/v0.1.2...HEAD
[0.1.2]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/v0.1.1...v0.1.2
[0.1.1]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/cardtechie/tradingcardapi-sdk-php/releases/tag/v0.1.0