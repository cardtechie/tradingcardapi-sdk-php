# API Response Validation

This document explains the API response validation system introduced to ensure API responses match expected schemas and catch API changes early in development.

## Overview

The Trading Card API PHP SDK now includes automatic response validation that validates API responses against expected schemas. This helps:

- **Early Detection**: Catch API changes immediately
- **Better Debugging**: Clear error messages for malformed responses  
- **API Compatibility**: Ensure SDK works with expected API version
- **Documentation**: Schemas serve as response documentation
- **Testing**: Validate mock responses in tests

## Configuration

Add validation configuration to your `config/tradingcardapi.php`:

```php
'validation' => [
    // Enable or disable response validation entirely
    'enabled' => env('TRADINGCARDAPI_VALIDATION', true),
    
    // Strict mode throws exceptions on validation failures
    'strict_mode' => env('TRADINGCARDAPI_STRICT_VALIDATION', false),
    
    // Log validation errors for debugging and monitoring
    'log_validation_errors' => env('TRADINGCARDAPI_LOG_VALIDATION', true),
    
    // Cache parsed schemas for better performance
    'cache_schemas' => env('TRADINGCARDAPI_CACHE_SCHEMAS', true),
],
```

### Environment Variables

Add these to your `.env` file:

```env
# Enable/disable validation (default: true)
TRADINGCARDAPI_VALIDATION=true

# Strict mode - throw exceptions on validation failures (default: false)
TRADINGCARDAPI_STRICT_VALIDATION=false

# Log validation errors (default: true)
TRADINGCARDAPI_LOG_VALIDATION=true

# Cache schemas for performance (default: true)
TRADINGCARDAPI_CACHE_SCHEMAS=true
```

## Validation Modes

### Lenient Mode (Default)

In lenient mode, validation errors are logged but don't stop execution:

```php
// .env
TRADINGCARDAPI_STRICT_VALIDATION=false
```

- Validation failures are logged as warnings
- API calls continue to work normally
- Useful for production environments
- Provides early warning of API changes

### Strict Mode

In strict mode, validation failures throw exceptions:

```php
// .env  
TRADINGCARDAPI_STRICT_VALIDATION=true
```

- Validation failures throw `ValidationException`
- API calls fail fast on invalid responses
- Useful for development and testing
- Ensures strict compliance with expected schemas

## Schema Definitions

The SDK includes schema definitions for all major resources:

### Available Schemas

- **CardSchema** - Card responses
- **PlayerSchema** - Player responses
- **TeamSchema** - Team responses
- **SetSchema** - Set responses
- **GenreSchema** - Genre responses
- **BrandSchema** - Brand responses
- **ManufacturerSchema** - Manufacturer responses
- **YearSchema** - Year responses
- **AttributeSchema** - Attribute responses
- **ObjectAttributeSchema** - Object attribute responses
- **PlayerteamSchema** - Player-team relationship responses
- **StatsSchema** - Statistics responses

### Schema Structure

Each schema extends `BaseSchema` and defines validation rules:

```php
class CardSchema extends BaseSchema
{
    public function getRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiRules(),
            $this->getMetaLinksRules(),
            $this->getCardSpecificRules()
        );
    }
    
    private function getCardSpecificRules(): array
    {
        return [
            'data.type' => 'required|string|in:cards,card',
            'data.attributes.name' => 'sometimes|string|nullable',
            'data.attributes.number' => 'sometimes|string|nullable',
            'data.attributes.year' => 'sometimes|integer|nullable',
            // ... more rules
        ];
    }
}
```

## Using the Validation System

### Automatic Validation

Validation happens automatically when making API calls:

```php
use CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk;

// This response will be automatically validated
$cards = TradingCardApiSdk::card()->getList();
```

### Manual Validation

You can also validate responses manually:

```php
use CardTechie\TradingCardApiSdk\Services\ResponseValidator;

$validator = new ResponseValidator();

$responseData = [
    'data' => [
        'id' => '123',
        'type' => 'cards',
        'attributes' => [
            'name' => 'Test Card',
        ],
    ],
];

$isValid = $validator->validate('card', $responseData, '/v1/cards/123');

if (!$isValid) {
    $errors = $validator->getErrors();
    // Handle validation errors
}
```

### Accessing Validator Instance

You can access the validator from any resource:

```php
$cardResource = TradingCardApiSdk::card();
$cards = $cardResource->getList();

// Get validator to check validation results
$validator = $cardResource->getValidator();

if ($validator && !$validator->isValid()) {
    $errors = $validator->getErrors();
    Log::warning('API response validation failed', $errors);
}
```

## Error Handling

### Validation Errors

Validation errors are returned as Laravel validation error arrays:

```php
[
    'data.id' => ['The data.id field is required.'],
    'data.attributes.year' => ['The data.attributes.year must be an integer.'],
]
```

### Exception Handling (Strict Mode)

```php
use Illuminate\Validation\ValidationException;

try {
    $cards = TradingCardApiSdk::card()->getList();
} catch (ValidationException $e) {
    $errors = $e->validator->errors()->toArray();
    
    Log::error('API response validation failed', [
        'errors' => $errors,
        'response' => $e->validator->getData(),
    ]);
    
    // Handle validation failure
}
```

## Performance Considerations

### Benchmarks

Based on performance tests:

- **Single response validation**: < 10ms
- **Large collections (100+ items)**: < 500ms
- **Disabled validation**: < 1ms
- **Memory usage**: < 1MB per validation

### Optimization Tips

1. **Disable in Production**: For high-performance production environments:
   ```php
   'validation' => [
       'enabled' => false,
   ]
   ```

2. **Enable Schema Caching**: Improves performance for repeated validations:
   ```php
   'validation' => [
       'cache_schemas' => true,
   ]
   ```

3. **Disable Logging**: Reduce I/O overhead in production:
   ```php
   'validation' => [
       'log_validation_errors' => false,
   ]
   ```

## Custom Schemas

You can create custom schemas for specialized endpoints:

```php
use CardTechie\TradingCardApiSdk\Schemas\BaseSchema;

class CustomSchema extends BaseSchema
{
    public function getRules(): array
    {
        return [
            'custom_field' => 'required|string',
            'optional_field' => 'sometimes|integer',
        ];
    }
}
```

## Monitoring and Alerting

### Log Monitoring

Monitor validation errors in your logs:

```bash
# Search for validation failures
grep "API response validation failed" /path/to/laravel.log

# Monitor validation error patterns
grep "TradingCardAPI SDK" /path/to/laravel.log | grep "validation"
```

### Custom Alerting

Set up alerts for validation failures:

```php
use Illuminate\Support\Facades\Log;

// Custom log channel for API validation errors
'channels' => [
    'api_validation' => [
        'driver' => 'single',
        'path' => storage_path('logs/api-validation.log'),
        'level' => 'warning',
    ],
],

// In ResponseValidator service
if ($this->config['log_validation_errors']) {
    Log::channel('api_validation')->warning($errorMessage, [
        'resource_type' => $resourceType,
        'endpoint' => $endpoint,
        'errors' => $this->errors,
    ]);
}
```

## Troubleshooting

### Common Issues

1. **Schema Not Found**
   ```
   WARNING: No schema defined for resource type: customresource
   ```
   - Create a schema class for the resource type
   - Or disable validation for specific endpoints

2. **Validation Too Strict**
   ```
   The data.attributes.field must be an integer.
   ```
   - Check if API response format has changed
   - Update schema rules if necessary
   - Use lenient mode during API transitions

3. **Performance Issues**
   ```
   Validation taking too long
   ```
   - Enable schema caching
   - Disable validation for large collections
   - Consider async validation for bulk operations

### Debug Mode

Enable detailed debugging:

```php
// .env
TRADINGCARDAPI_VALIDATION=true
TRADINGCARDAPI_LOG_VALIDATION=true
LOG_LEVEL=debug

// This will log all validation attempts and results
```

## Best Practices

1. **Development**: Use strict mode to catch issues early
2. **Staging**: Use lenient mode with logging for monitoring
3. **Production**: Consider disabling for performance, or use lenient mode
4. **Testing**: Always validate mock responses in tests
5. **Monitoring**: Set up alerts for validation failures
6. **Documentation**: Use schemas as API response documentation

## Migration Guide

### Upgrading from Previous Versions

The validation system is backward compatible and enabled by default. To maintain existing behavior:

1. **Disable validation**:
   ```php
   'validation' => ['enabled' => false]
   ```

2. **Use lenient mode** (default):
   ```php
   'validation' => ['strict_mode' => false]
   ```

3. **Monitor logs** for validation warnings:
   ```bash
   tail -f /path/to/laravel.log | grep "API response validation"
   ```