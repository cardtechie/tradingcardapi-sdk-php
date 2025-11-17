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

## 🔐 Authentication

The SDK supports two authentication methods:

### Personal Access Token (PAT) - Recommended for Simple Use Cases

Personal Access Tokens provide a simpler authentication flow ideal for:
- Testing and development
- Single-user applications
- AI/GPT integrations
- Simple scripts and tools

```php
use CardTechie\TradingCardApiSdk\TradingCardApi;

// Method 1: Direct instantiation with token
$api = TradingCardApi::withPersonalAccessToken('your-pat-token-here');

// Method 2: From configuration
$api = TradingCardApi::withPersonalAccessToken(
    config('tradingcardapi.personal_access_token')
);

// Use normally
$cards = $api->card()->list();
```

**Environment Configuration:**

```env
# .env
TRADINGCARDAPI_URL=https://api.tradingcardapi.com
TRADINGCARDAPI_PAT=your-personal-access-token
```

**Security Considerations:**
- ⚠️ PAT tokens are long-lived and should be kept secret
- Never commit tokens to version control
- Always use environment variables
- Rotate tokens periodically
- Consider OAuth2 for production multi-user applications

### OAuth2 Client Credentials - Recommended for Production

OAuth2 Client Credentials flow is ideal for:
- Production applications
- Multi-user systems
- Applications requiring token refresh

```php
use CardTechie\TradingCardApiSdk\TradingCardApi;

// Method 1: Using static method
$api = TradingCardApi::withClientCredentials(
    clientId: config('tradingcardapi.client_id'),
    clientSecret: config('tradingcardapi.client_secret')
);

// Method 2: Using default constructor (uses config)
$api = new TradingCardApi();

// Use normally
$cards = $api->card()->list();
```

**Environment Configuration:**

```env
# .env
TRADINGCARDAPI_URL=https://api.tradingcardapi.com
TRADINGCARDAPI_CLIENT_ID=your-client-id
TRADINGCARDAPI_CLIENT_SECRET=your-client-secret
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

## 📸 Working with Card Images

The SDK provides comprehensive support for uploading, managing, and retrieving card images with automatic thumbnail generation and CDN delivery.

### Upload Card Images

```php
use CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk;

// Upload from file path
$image = TradingCardApiSdk::cardImage()->upload(
    file: '/path/to/card-front.jpg',
    cardId: 'card-uuid-here',
    imageType: 'front'
);

// Upload from Laravel UploadedFile (e.g., from request)
$image = TradingCardApiSdk::cardImage()->upload(
    file: $request->file('image'),
    cardId: 'card-uuid-here',
    imageType: 'back'
);

// Upload with additional attributes
$image = TradingCardApiSdk::cardImage()->upload(
    file: '/path/to/image.jpg',
    cardId: 'card-uuid',
    imageType: 'front',
    attributes: ['storage_disk' => 's3']
);

echo $image->id;           // UUID of uploaded image
echo $image->download_url; // CDN URL for the image
```

### Retrieve Card Images

```php
// Get a specific card image with metadata
$image = TradingCardApiSdk::cardImage()->get('image-uuid');

// Access image properties
echo $image->file_size;    // File size in bytes
echo $image->mime_type;    // e.g., "image/jpeg"
echo $image->width;        // Image width in pixels
echo $image->height;       // Image height in pixels

// Get related card
$card = $image->card();
```

### Image Variants & CDN URLs

The API automatically generates thumbnail variants (small, medium, large) for uploaded images:

```php
$image = TradingCardApiSdk::cardImage()->get('image-uuid');

// Get original image URL
echo $image->getCdnUrl();           // Original size
echo $image->getCdnUrl('original'); // Explicit original

// Get thumbnail URLs
echo $image->getCdnUrl('small');    // Small thumbnail (150px)
echo $image->getCdnUrl('medium');   // Medium thumbnail (300px)
echo $image->getCdnUrl('large');    // Large thumbnail (600px)

// Get cache-busted versioned URLs
echo $image->getVersionedUrl();         // Original with version parameter
echo $image->getVersionedUrl('small');  // Small variant with version

// Check if variant exists
if ($image->hasVariant('small')) {
    echo $image->getVariantUrl('small');
}

// Get all available variant sizes
$sizes = $image->getVariantSizes(); // ['small', 'medium', 'large']
```

### Responsive Images

Generate responsive image markup for optimal loading:

```php
$image = TradingCardApiSdk::cardImage()->get('image-uuid');

// Get srcset for responsive images
echo "<img src='{$image->download_url}'
           srcset='{$image->srcset}'
           sizes='{$image->sizes}'
           alt='Card Image' />";

// Output example:
// <img src="https://cdn.example.com/image.jpg"
//      srcset="https://cdn.example.com/small.jpg 150w,
//              https://cdn.example.com/medium.jpg 300w,
//              https://cdn.example.com/image.jpg 600w"
//      sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
//      alt="Card Image" />
```

### List and Filter Card Images

```php
// List all card images with pagination
$images = TradingCardApiSdk::cardImage()->list([
    'page' => 1,
    'limit' => 50,
]);

// Filter by card
$images = TradingCardApiSdk::cardImage()->list([
    'filter' => ['card_id' => 'card-uuid'],
    'include' => 'card',
]);

// Iterate through paginated results
foreach ($images as $image) {
    echo "{$image->image_type}: {$image->download_url}\n";
}
```

### Update Image Metadata

```php
// Update image type or other metadata
$image = TradingCardApiSdk::cardImage()->update('image-uuid', [
    'image_type' => 'back',  // Change from front to back
]);
```

### Delete Card Images

```php
// Soft delete a card image
TradingCardApiSdk::cardImage()->delete('image-uuid');
```

### Get Download URLs

```php
// Get download URL for specific size
$url = TradingCardApiSdk::cardImage()->getDownloadUrl('image-uuid');          // Original
$url = TradingCardApiSdk::cardImage()->getDownloadUrl('image-uuid', 'small'); // Small variant
```

### Access Card Images from a Card

You can load a card with its images and access them using convenient Collection-based methods:

```php
use CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk;

// Load card with images included
$card = TradingCardApiSdk::card()->get('card-uuid', [
    'include' => 'images',
]);

// Get all images as a Collection
$images = $card->images(); // Returns Collection<CardImage>

// Use Collection methods for filtering and manipulation
$frontImages = $images->filter(fn($img) => $img->image_type === 'front');
$downloadUrls = $images->pluck('download_url');

// Check if card has any images
if ($card->hasImages()) {
    echo "This card has {$images->count()} image(s)";
}

// Get specific images using convenience methods
$frontImage = $card->getFrontImage();
if ($frontImage) {
    echo "Front image: {$frontImage->download_url}";
}

$backImage = $card->getBackImage();
if ($backImage) {
    echo "Back image: {$backImage->download_url}";
}
```

## 📊 Working with Set Sources (Data Provenance)

The SDK provides support for tracking data provenance for trading card sets through the Set Sources API. Track where checklist, metadata, and image data came from.

### Create Set Source

```php
use CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk;

// Create checklist source
$source = TradingCardApiSdk::setSource()->create([
    'set_id' => 'set-uuid-here',
    'source_type' => 'checklist',
    'source_name' => 'Beckett',
    'source_url' => 'https://www.beckett.com/...',
    'verified_at' => now(),
]);

echo $source->id;          // UUID of created source
echo $source->source_type; // "checklist"
```

### Get Set Source

```php
$source = TradingCardApiSdk::setSource()->get('source-uuid', [
    'include' => 'set',
]);

echo $source->source_name;  // e.g., "Beckett"
echo $source->source_type;  // e.g., "checklist"
echo $source->set->name;    // e.g., "1989 Topps Baseball" (if included)
```

### Get Set Sources from a Set

```php
// Fetch a set with its sources included
$set = TradingCardApiSdk::set()->get('set-uuid', [
    'include' => 'set-sources',
]);

// Get all sources as a Collection
$sources = $set->sources(); // Returns Collection<SetSource>

// Use Collection methods for filtering and manipulation
$verifiedSources = $sources->filter(fn($s) => $s->verified_at !== null);
$sourceNames = $sources->pluck('source_name');

// Check if set has any sources
if ($set->hasSources()) {
    echo "This set has {$sources->count()} source(s)";
}

// Get specific source types using convenience methods
$checklistSource = $set->getChecklistSource();
if ($checklistSource) {
    echo "Checklist source: {$checklistSource->source_name}";
}

$metadataSource = $set->getMetadataSource();
if ($metadataSource) {
    echo "Metadata source: {$metadataSource->source_name}";
}

$imagesSource = $set->getImagesSource();
if ($imagesSource) {
    echo "Images source: {$imagesSource->source_name}";
}

// Or iterate directly (Collections are iterable)
foreach ($set->sources() as $source) {
    echo "{$source->source_type}: {$source->source_name}\n";
}
```

### List Set Sources

```php
// List all set sources with pagination
$sources = TradingCardApiSdk::setSource()->list([
    'page' => 1,
    'limit' => 50,
]);

// Filter by set
$sources = TradingCardApiSdk::setSource()->list([
    'filter' => ['set_id' => 'set-uuid'],
    'include' => 'set',
]);

// Iterate through paginated results
foreach ($sources as $source) {
    echo "{$source->source_type}: {$source->source_name}\n";
}
```

### Update Set Source

```php
// Update source URL and verification timestamp
$source = TradingCardApiSdk::setSource()->update('source-uuid', [
    'source_url' => 'https://updated-url.com',
    'verified_at' => now(),
]);
```

### Delete Set Source

```php
// Delete a set source
TradingCardApiSdk::setSource()->delete('source-uuid');
```

### Source Types

Valid values for `source_type`:

- **`checklist`** - Source of card checklist data
- **`metadata`** - Source of set metadata (year, manufacturer, etc.)
- **`images`** - Source of card images

**Note:** Each set can have only one source per type. The API enforces unique constraints.

### Usage Examples

**Track multiple sources for a set:**

```php
// Checklist from Beckett
$checklistSource = TradingCardApiSdk::setSource()->create([
    'set_id' => 'set-123',
    'source_type' => 'checklist',
    'source_name' => 'Beckett',
    'source_url' => 'https://www.beckett.com/...',
]);

// Metadata from CardboardConnection
$metadataSource = TradingCardApiSdk::setSource()->create([
    'set_id' => 'set-123',
    'source_type' => 'metadata',
    'source_name' => 'CardboardConnection',
    'source_url' => 'https://www.cardboardconnection.com/...',
]);

// Images from eBay
$imagesSource = TradingCardApiSdk::setSource()->create([
    'set_id' => 'set-123',
    'source_type' => 'images',
    'source_name' => 'eBay',
    'source_url' => 'https://www.ebay.com/...',
]);
```

**Update verification timestamp:**

```php
$source = TradingCardApiSdk::setSource()->update('source-uuid', [
    'verified_at' => now(), // Mark as verified today
]);

echo $source->verified_at->format('Y-m-d'); // Carbon instance
```

## 📚 Available Resources

The SDK provides access to the following Trading Card API resources:

| Resource | Description | Methods |
|----------|-------------|---------|
| **Cards** | Individual trading cards | `get()`, `create()`, `update()`, `delete()` |
| **CardImages** | Card image uploads and management | `get()`, `list()`, `upload()`, `update()`, `delete()`, `getDownloadUrl()` |
| **Sets** | Card sets and collections | `get()`, `list()`, `create()`, `update()`, `delete()`, `checklist($id)`, `addMissingCards($id)`, `addChecklist($request, $id)` |
| **SetSources** | Data provenance tracking for sets | `get()`, `list()`, `create()`, `update()`, `delete()` |
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

## 🔄 API Versions

The Trading Card API provides both v1 and v2 endpoints with different response structures to support various JSON:API compliance levels.

### v1 Endpoints (Default)

**The SDK uses v1 endpoints by default.** These endpoints follow a relationship-focused JSON:API pattern where related resources are returned in the `included` array alongside the primary resource.

**Example:** `GET /v1/sets/{id}/checklist`

```json
{
  "data": {
    "type": "sets",
    "id": "set-uuid",
    "attributes": {
      "name": "1989 Topps Baseball"
    }
  },
  "included": [
    {
      "type": "cards",
      "id": "card-1",
      "attributes": { "number": "1", "name": "Player 1" }
    },
    {
      "type": "cards",
      "id": "card-2",
      "attributes": { "number": "2", "name": "Player 2" }
    }
  ]
}
```

**Characteristics:**
- Primary resource in `data` object
- Related resources in `included` array
- Follows JSON:API relationship pattern
- Optimal for accessing both the parent resource and related data

### v2 Endpoints (Stricter JSON:API Compliance)

The API also provides v2 endpoints with stricter JSON:API compliance. These endpoints return collections as the primary `data` array when accessing relationship endpoints.

**Example:** `GET /v2/sets/{id}/checklist`

```json
{
  "data": [
    {
      "type": "cards",
      "id": "card-1",
      "attributes": { "number": "1", "name": "Player 1" }
    },
    {
      "type": "cards",
      "id": "card-2",
      "attributes": { "number": "2", "name": "Player 2" }
    }
  ]
}
```

**Characteristics:**
- Collection resources in `data` array
- More JSON:API compliant for collection endpoints
- Focuses on the requested collection rather than parent resource
- Cleaner structure when you only need the related resources

### Version Support

**Current SDK Support:** v1 endpoints only

**Future Plans:** v2 endpoint support may be added in a future SDK version based on user feedback and demand. Both versions return the same data, just structured differently according to JSON:API specifications.

**Need v2 Support?** If you require v2 endpoint support, please [open an issue](https://github.com/cardtechie/tradingcardapi-sdk-php/issues) describing your use case.

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