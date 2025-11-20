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
  - **Year Model Changes:**
    - `Year::sets()` - Now returns `Collection<Set>` instead of `array`
    - Added `Year::hasSets()` helper method
  - **Brand Model Changes:**
    - `Brand::sets()` - Now returns `Collection<Set>` instead of `array`
    - Added `Brand::hasSets()` helper method
  - **Manufacturer Model Changes:**
    - `Manufacturer::sets()` - Now returns `Collection<Set>` instead of `array`
    - Added `Manufacturer::hasSets()` helper method
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

### Fixed

- **Content-Type Header for Mutating Requests** - Fixed JSON:API Content-Type header for POST/PUT/PATCH requests
  - Changed `ApiRequest` trait to send `Content-Type: application/vnd.api+json` for mutating requests (POST, PUT, PATCH)
  - Previously sent `application/json` causing 415 Unsupported Media Type errors
  - GET and DELETE requests no longer send Content-Type header (not needed for requests without body)
  - Custom Content-Type headers can still override the default if needed
  - Fixes issue #139 - Resolves admin application Dusk test failures

## [0.1.10] - 2025-10-30

### Added

- **OAuth Scope Configuration Support** - Configurable OAuth scopes for fine-grained API access control
  - Added `scope` configuration option to `config/tradingcardapi.php`
  - Default scope: `read:published` for backwards compatibility
  - Supports space-separated multiple scopes (e.g., `read:all-status write delete`)
  - Environment variable: `TRADINGCARDAPI_SCOPE`
  - Updated `ApiRequest::retrieveToken()` to request configured scopes instead of empty string
  - Documented all available scopes and their purposes in README.md

### Enhanced

- **OAuth Token Authentication** - Modified OAuth token request to use configured scopes
  - Changed `src/Resources/Traits/ApiRequest.php:120` from hardcoded empty scope to configurable scope
  - Enables write operations, delete operations, and access to draft/archived content
  - Unblocks admin applications requiring elevated permissions

## [0.1.9] - 2025-09-28

### Added

- **Complete Year Parent/Child Relationship Support** - Full hierarchical year functionality
  - `Year::parent()` method for retrieving parent year relationship
  - `Year::children()` method for retrieving child year relationships
  - `Year::hasParent()` helper method for checking parent existence
  - `Year::hasChildren()` helper method for checking child existence
  - `Year::getDisplayName()` method for consistent display across applications
  - `Year::listParents()` resource method for filtering parent years
  - `Year::listChildren($parentId)` resource method for filtering child years

### Enhanced

- **YearSchema Field Mapping** - Resolved field mapping inconsistencies for admin integration
  - Added `name` field validation to support database schema requirements
  - Added `parent_year` field validation for relationship functionality
  - Added `YearSchema::getCollectionRules()` method for bulk operation validation
  - Maintained backward compatibility with existing `year` and `description` fields

### Fixed

- **Year Resource Pagination Crash** - Added defensive handling for missing meta property
  - Fixed division by zero error when API response lacks pagination metadata
  - Added fallback pagination values using request params and response data
  - Applied consistent pagination handling matching other SDK resources

## [0.1.8] - 2025-09-28

### Added

- ManufacturerSchema::getCollectionRules() method for proper collection response validation
- Enhanced defensive pagination handling with multi-level isset() checks

### Fixed

- Manufacturer resource pagination crash when API response missing meta property
- Added defensive handling for missing pagination metadata in Manufacturer::list() method
- Fixed ManufacturerSchema validation failing on collection endpoints

## [0.1.7] - 2025-09-28

### Fixed

- Brand resource pagination crash when API response missing meta property
- Added defensive handling for missing pagination metadata in Brand::list() method

## [0.1.6] - 2025-09-27

### Added

- Complete Team resource CRUD operations (get, update, delete, list, listDeleted, deleted)

### Fixed

- API pagination handling when meta property is missing

## [0.1.5] - 2025-09-27

### Fixed

- Removed hardcoded version from package to improve release automation
- Enhanced release documentation generation

## [0.1.4] - 2025-09-27

### Changed

- Trigger Packagist update for v0.1.3 release

## [0.1.3] - 2025-09-27

### Added

- Comprehensive Player SDK functionality to match Card/Genre resource patterns
  - Complete CRUD operations for Player resource
  - Player model with full attribute support
  - Player schema validation
  - Comprehensive test coverage

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

[Unreleased]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.10...HEAD
[0.1.10]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.9...0.1.10
[0.1.9]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.8...0.1.9
[0.1.8]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.7...0.1.8
[0.1.7]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.6...0.1.7
[0.1.6]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.5...0.1.6
[0.1.5]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.4...0.1.5
[0.1.4]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.3...0.1.4
[0.1.3]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.2...0.1.3
[0.1.2]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.1...0.1.2
[0.1.1]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/v0.1.0...0.1.1
[0.1.0]: https://github.com/cardtechie/tradingcardapi-sdk-php/releases/tag/v0.1.0