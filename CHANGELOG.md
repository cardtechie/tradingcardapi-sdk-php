# Changelog

All notable changes to `Trading Card API PHP SDK` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

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
    - `Card::getImages()` - Returns `Collection<CardImage>` for fluent data manipulation
    - `Card::hasImages()` - Check if card has any images
    - `Card::getFrontImage()` - Convenience method to get front image
    - `Card::getBackImage()` - Convenience method to get back image
    - Full Collection API support (filter, map, pluck, etc.)
  - **Set-to-Sources Relationship Helpers**
    - `Set::getSources()` - Returns `Collection<SetSource>` for fluent data manipulation
    - `Set::hasSources()` - Check if set has any sources
    - `Set::getChecklistSource()` - Convenience method to get checklist source
    - `Set::getMetadataSource()` - Convenience method to get metadata source
    - `Set::getImagesSource()` - Convenience method to get images source
    - Full Collection API support for source filtering and manipulation
  - Comprehensive test coverage for all relationship methods
  - Updated documentation with Collection-based examples
  - Legacy array-based methods (`sources()`) remain available for backward compatibility

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