# Changelog

All notable changes to `Trading Card API PHP SDK` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **Personal Access Token (PAT) Authentication Support** - Alternative authentication method for simpler use cases (Trading Card API v0.6.0+)
  - New `TradingCardApi::withPersonalAccessToken($token)` static factory method
  - New `TradingCardApi::withClientCredentials($clientId, $clientSecret)` static factory method
  - Dual authentication support: PAT for simple apps, OAuth2 for production
  - **Auto-detection**: SDK automatically uses PAT mode when `TRADINGCARDAPI_PAT` is set and OAuth2 credentials are empty
  - Updated `ApiRequest` trait to handle both auth types
  - PAT tokens bypass OAuth2 token exchange (direct Bearer token usage)
  - OAuth2 token caching now uses unique keys per credentials (prevents token sharing between instances)
  - Configuration support via `TRADINGCARDAPI_PAT` environment variable
  - Backward compatible - existing OAuth2 usage continues to work
  - Comprehensive security documentation and warnings
  - Full test coverage for both authentication methods and auto-detection
  - Ideal for testing, development, AI integrations, and single-user apps

- **API Versions Documentation** - Added comprehensive documentation explaining v1 and v2 endpoint differences
  - New "API Versions" section in README.md
  - Explains v1 endpoints (relationship-focused, SDK default)
  - Explains v2 endpoints (stricter JSON:API compliance)
  - Includes JSON response examples for both versions
  - Notes SDK's current v1-only support with future v2 consideration

- **Card Images API Support** - Complete implementation of Card Images functionality (Trading Card API v0.7.0)
  - New `CardImage` model with properties for image metadata and relationships
  - New `CardImage` resource class with full CRUD operations
  - Multipart file upload support in `ApiRequest` trait for handling binary file uploads
  - Support for both file path strings and Laravel `UploadedFile` instances
  - Image variant handling (small, medium, large thumbnails)
  - CDN URL helpers: `getCdnUrl()`, `getVersionedUrl()`, `getVariantUrl()`
  - Responsive image support with `srcset` and `sizes` attributes
  - Variant detection methods: `hasVariant()`, `getVariantSizes()`
  - `CardImageSchema` for API response validation
  - `cardImage()` method added to main `TradingCardApi` class
  - Comprehensive test coverage for model, resource, and schema
  - Full documentation with usage examples in README.md

- **Set Sources API Support** - Data provenance tracking for trading card sets (Trading Card API v0.6.0)
  - New `SetSource` model for tracking data sources (checklist, metadata, images)
  - New `SetSource` resource class with full CRUD operations
  - Support for three source types: `checklist`, `metadata`, `images`
  - Unique constraint handling (one source per type per set)
  - Verification timestamp tracking with `verified_at` field
  - `SetSourceSchema` for API response validation
  - `setSource()` method added to main `TradingCardApi` class
  - Response type mapping for `set-sources` resource
  - Comprehensive test coverage for model, resource, and schema
  - Full documentation with usage examples in README.md

- **Collection-Based Relationship Methods** - Modern Laravel-style relationship access
  - **Card-to-Images Relationship Support**
    - `Card::images()` - Returns `Collection<CardImage>` for fluent data manipulation
    - `Card::hasImages()` - Check if card has any images
    - `Card::getFrontImage()` - Convenience method to get front image
    - `Card::getBackImage()` - Convenience method to get back image
    - Full Collection API support (filter, map, pluck, etc.)
  - **Set-to-Sources Relationship Support**
    - `Set::sources()` - Returns `Collection<SetSource>` for fluent data manipulation
    - `Set::hasSources()` - Check if set has any sources
    - `Set::getChecklistSource()` - Convenience method to get checklist source
    - `Set::getMetadataSource()` - Convenience method to get metadata source
    - `Set::getImagesSource()` - Convenience method to get images source
    - Full Collection API support for source filtering and manipulation
  - Comprehensive test coverage for all relationship methods
  - Updated documentation with Collection-based examples

### Changed

- **⚠️ BREAKING CHANGE: Migrated Array-Based Relationship Methods to Collections** - Consistent Collection API across all model relationships
  - **Set Model Changes:**
    - `Set::subsets()` - Now returns `Collection<Set>` instead of `array`
    - `Set::checklist()` - Now returns `Collection<Card>` instead of `array`
    - Added `Set::hasSubsets()` helper method
    - Added `Set::hasChecklist()` helper method
    - Refactored navigation methods to use Collection search (no longer stateful)
    - Removed private `$checklistIndex` property (cleaner functional approach)
  - **Card Model Changes:**
    - `Card::oncard()` - Now returns `Collection<mixed>` instead of `?array`
    - `Card::extraAttributes()` - Now returns `Collection<mixed>` instead of `?array`
    - Added `Card::hasOncard()` helper method
    - Added `Card::hasExtraAttributes()` helper method
  - **ObjectAttribute Model Changes:**
    - `ObjectAttribute::cards()` - Now returns `Collection<Card>` instead of `array`
    - Added `ObjectAttribute::hasCards()` helper method
  - **Migration Guide:**
    - Most iteration code continues to work (Collections are iterable)
    - Array functions need updating:
      - `count($model->relationship())` → `$model->relationship()->count()`
      - `array_filter($array, ...)` → `$collection->filter(...)`
      - `array_map($callback, $array)` → `$collection->map($callback)`
    - Null checks need updating:
      - `if ($card->oncard() === null)` → `if ($card->hasOncard())`
      - `if (empty($set->checklist()))` → `if ($set->hasChecklist())`
  - **Benefits:**
    - Consistent API across all relationships
    - Access to 80+ Laravel Collection methods
    - Better IDE support with type hints
    - Cleaner, more maintainable code
  - Full test coverage with Collection method examples
  - See issue #133 for implementation details

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

[Unreleased]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/cardtechie/tradingcardapi-sdk-php/releases/tag/v0.1.0