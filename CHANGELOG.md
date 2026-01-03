# Changelog

All notable changes to `Trading Card API PHP SDK` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.17] - 2026-01-03

### Fixed

- **SetSource JSON:API type correction** - Fixed request type from `set-sources` to `set_sources` to match API expectations (Issue #158)

## [0.1.15] - 2025-12-31

### Added

- **SetSource Resource Support** - New resource for managing set data sources (Issue #156)
  - Added `SetSource` model with `set()` relationship method
  - Added `SetSource` resource with full CRUD operations: `get()`, `list()`, `create()`, `update()`, `delete()`
  - Added `forSet($setId)` method to retrieve all sources for a specific set
  - Added `SetSourceSchema` for API response validation
  - Added `sources()` relationship method to `Set` model
  - Added `setSource()` accessor method to `TradingCardApi` class
  - Set sources track where checklist data, metadata, and images come from (e.g., Beckett, TCDB, CardboardConnection)

### Changed

- **Response Type Normalization** - Improved handling of hyphenated API types
  - Added `normalizeType()` method to `Response` class for consistent type-to-class mapping
  - Supports hyphenated types like `set-sources` converting to `SetSource` model class
  - Consolidated special type handling (parentset, subset, checklist) into single method
  - Added `ALLOWED_MODEL_TYPES` whitelist for security validation

## [0.1.14] - 2025-12-20

### Added

- **is_variation Support for Set Model** - New boolean field to distinguish variations from parallels (Issue #151)
  - Added `is_variation` property to Set model for API response handling
  - Added validation rules in SetSchema for single and collection responses
  - Variations are sets that share card numbers with base sets but have different visual treatments (e.g., Tin Type, Chrome)

## [0.1.13] - 2025-12-13

### Added

- **OnCardable Trait for Player and Team Models** - Enables independent oncard relationships (Issue #148)
  - Added `OnCardable` trait to `Player` model with `onCardable()` and `prepare()` methods
  - Added `OnCardable` trait to `Team` model with `onCardable()` and `prepare()` methods
  - Allows cards to have direct player-only or team-only associations without requiring a Playerteam relationship
  - Supports both UUID and name-based lookups in `prepare()` method

## [0.1.12] - 2025-12-01

### Added

- **Stats Endpoint Support** - New methods for entity count tracking and analytics (Issue #144)
  - `Stats::getCounts()` - Get current counts for all entity types (total, published, draft, archived)
  - `Stats::getSnapshots(array $filters = [])` - Get historical snapshots with date range filtering
  - `Stats::getGrowth(string $period = '7d')` - Get growth metrics (daily/weekly/monthly changes)
  - New DTOs for type-safe responses:
    - `CountsResponse` with `EntityCount` objects
    - `SnapshotsResponse` with `Snapshot` objects
    - `GrowthResponse` with `GrowthMetric` objects
  - Helper methods `getByEntityType()` for easy access to specific entity metrics

## [0.1.11] - 2025-11-30

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

### Enhanced
- **OAuth Token Authentication** - Modified OAuth token request to use configured scopes
  - Changed `src/Resources/Traits/ApiRequest.php:120` from hardcoded empty scope to configurable scope
  - Enables write operations, delete operations, and access to draft/archived content
  - Unblocks admin applications requiring elevated permissions

### Documentation
- Added OAuth Scopes section to README.md with comprehensive examples
- Documented all available scopes and their purposes
- Provided configuration examples for different use cases (read-only, admin, content management)

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
- Year resource integration gaps preventing admin interface migration
- Field mapping inconsistencies between API database and SDK schema
- Missing validation rules for Year parent relationships

## [0.1.8] - 2025-09-28

### Fixed
- Manufacturer resource pagination crash when API response missing meta property
- Added defensive handling for missing pagination metadata in Manufacturer::list() method
- Added collection validation rules for Manufacturer schema to support array responses
- Fixed ManufacturerSchema validation failing on collection endpoints

### Added
- ManufacturerSchema::getCollectionRules() method for proper collection response validation
- Enhanced defensive pagination handling with multi-level isset() checks

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

### Added
- Enhanced create() method for Team resource with relationship support

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

[Unreleased]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.17...HEAD
[0.1.17]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.15...0.1.17
[0.1.15]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.14...0.1.15
[0.1.14]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.13...0.1.14
[0.1.13]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.12...0.1.13
[0.1.12]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.11...0.1.12
[0.1.11]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.10...0.1.11
[0.1.10]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.9...0.1.10
[0.1.9]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.8...0.1.9
[0.1.8]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.7...0.1.8
[0.1.7]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.6...0.1.7
[0.1.6]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.5...0.1.6
[0.1.5]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.4...0.1.5
[0.1.4]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.3...0.1.4
[0.1.3]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.2...0.1.3
[0.1.2]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.1...0.1.2
[0.1.1]: https://github.com/cardtechie/tradingcardapi-sdk-php/compare/0.1.0...0.1.1
[0.1.0]: https://github.com/cardtechie/tradingcardapi-sdk-php/releases/tag/0.1.0
