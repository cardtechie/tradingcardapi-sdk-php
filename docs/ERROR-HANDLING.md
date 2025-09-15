# Error Handling

The Trading Card API SDK provides comprehensive error handling with specific exception classes for different types of API errors. This guide covers all available exceptions, their use cases, and how to handle them effectively in your application.

## Exception Hierarchy

All SDK exceptions extend from `TradingCardApiException`, which provides common properties and methods for error handling.

```
TradingCardApiException
├── AuthenticationException (401)
├── AuthorizationException (403)
├── ValidationException (422)
├── ResourceNotFoundException (404)
│   ├── CardNotFoundException
│   ├── PlayerNotFoundException
│   └── SetNotFoundException
├── RateLimitException (429)
├── ServerException (5xx)
└── NetworkException (connection issues)
```

## Common Exception Properties

All exceptions provide access to:

- **HTTP Status Code**: The HTTP response status code
- **API Error Code**: The specific error code from the API
- **API Errors**: Detailed error information from the API
- **Context**: Additional debugging information
- **Original Exception**: The underlying exception that caused this error

### Base Exception Methods

```php
use CardTechie\TradingCardApiSdk\Exceptions\TradingCardApiException;

try {
    $cards = $api->card()->all();
} catch (TradingCardApiException $e) {
    // Get basic information
    echo $e->getMessage();              // Human-readable error message
    echo $e->getCode();                 // Exception code
    
    // Get API-specific information
    echo $e->getHttpStatusCode();       // HTTP status code (e.g., 404)
    echo $e->getApiErrorCode();         // API error code (e.g., 'card_not_found')
    print_r($e->getApiErrors());        // Detailed API error array
    print_r($e->getContext());          // Additional context for debugging
    
    // Get first API error message
    echo $e->getApiErrorMessage();      // First error detail or title
    
    // Check error type
    if ($e->isClientError()) {
        // 4xx errors - client-side issues
    }
    if ($e->isServerError()) {
        // 5xx errors - server-side issues
    }
    
    // Convert to array for logging
    $errorData = $e->toArray();
    Log::error('API Error', $errorData);
}
```

## Specific Exception Types

### AuthenticationException (401)

Thrown when authentication fails, typically due to invalid credentials or expired tokens.

```php
use CardTechie\TradingCardApiSdk\Exceptions\AuthenticationException;

try {
    $cards = $api->card()->all();
} catch (AuthenticationException $e) {
    // Handle authentication errors
    if ($e->getApiErrorCode() === 'token_expired') {
        // Clear cached token and retry
        Cache::forget('tcapi_token');
        // Retry request...
    } else {
        // Invalid credentials - notify admin
        Log::alert('Invalid API credentials', $e->toArray());
    }
}

// Static factory methods
$invalidCreds = AuthenticationException::invalidCredentials(['client_id' => 'test']);
$expiredToken = AuthenticationException::expiredToken(['token' => 'abc123']);
```

### AuthorizationException (403)

Thrown when the authenticated user lacks permission for the requested resource.

```php
use CardTechie\TradingCardApiSdk\Exceptions\AuthorizationException;

try {
    $premiumCards = $api->card()->premium();
} catch (AuthorizationException $e) {
    if ($e->getApiErrorCode() === 'insufficient_permissions') {
        // User needs upgrade
        return redirect()->route('upgrade.subscription');
    }
    
    if ($e->getApiErrorCode() === 'account_suspended') {
        // Account issue
        return redirect()->route('account.suspended');
    }
}

// Static factory methods
$insufficientPerms = AuthorizationException::insufficientPermissions('premium cards');
$suspended = AuthorizationException::accountSuspended();
```

### ValidationException (422)

Thrown when request validation fails. Provides detailed field-level error information.

```php
use CardTechie\TradingCardApiSdk\Exceptions\ValidationException;

try {
    $card = $api->card()->create([
        'name' => '', // Invalid - empty name
        'year' => 'invalid' // Invalid - not a number
    ]);
} catch (ValidationException $e) {
    // Get all validation errors grouped by field
    $errors = $e->getValidationErrors();
    // $errors = [
    //     'name' => ['Name is required'],
    //     'year' => ['Year must be a valid integer']
    // ]
    
    // Check specific field errors
    if ($e->hasFieldError('name')) {
        $nameErrors = $e->getFieldErrors('name');
        foreach ($nameErrors as $error) {
            echo "Name error: $error\n";
        }
    }
    
    // Return validation errors to user
    return back()->withErrors($e->getValidationErrors());
}

// Static factory methods
$missingFields = ValidationException::missingRequiredFields(['name', 'year']);
$invalidValues = ValidationException::invalidFieldValues([
    'name' => 'Name must be at least 3 characters',
    'year' => 'Year must be between 1800 and current year'
]);
```

### ResourceNotFoundException (404)

Thrown when a requested resource is not found. Includes resource-specific subclasses.

```php
use CardTechie\TradingCardApiSdk\Exceptions\ResourceNotFoundException;
use CardTechie\TradingCardApiSdk\Exceptions\CardNotFoundException;

try {
    $card = $api->card()->find('nonexistent-id');
} catch (CardNotFoundException $e) {
    // Handle specific card not found
    Log::info('Card not found', [
        'card_id' => 'nonexistent-id',
        'user_id' => auth()->id()
    ]);
    
    return response()->json(['error' => 'Card not found'], 404);
} catch (ResourceNotFoundException $e) {
    // Handle generic resource not found
    return response()->json(['error' => 'Resource not found'], 404);
}

// Resource-specific factory methods
$cardNotFound = CardNotFoundException::byId('123');
$playerNotFound = PlayerNotFoundException::byName('John Doe');
$setNotFound = SetNotFoundException::byName('1989 Upper Deck');

// Generic factory methods  
$resourceNotFound = ResourceNotFoundException::resource('card', '123');
$endpointNotFound = ResourceNotFoundException::endpoint('/api/invalid');
```

### RateLimitException (429)

Thrown when API rate limits are exceeded. Provides rate limit information for retry logic.

```php
use CardTechie\TradingCardApiSdk\Exceptions\RateLimitException;

try {
    $cards = $api->card()->all();
} catch (RateLimitException $e) {
    // Get rate limit information
    $limit = $e->getRateLimit();           // Total requests allowed
    $remaining = $e->getRateLimitRemaining(); // Requests remaining
    $resetTime = $e->getRateLimitResetDateTime(); // When limit resets
    $retryAfter = $e->getRetryAfter();     // Seconds to wait
    
    // Calculate wait time
    $waitTime = $e->getSecondsUntilReset();
    
    Log::warning('Rate limit exceeded', [
        'limit' => $limit,
        'remaining' => $remaining,
        'reset_time' => $resetTime?->format('Y-m-d H:i:s'),
        'wait_time' => $waitTime
    ]);
    
    // Queue job for retry
    dispatch(new RetryApiRequest($requestData))->delay(now()->addSeconds($waitTime));
    
    return response()->json([
        'error' => 'Rate limit exceeded',
        'retry_after' => $waitTime
    ], 429);
}

// Create from response headers
$rateLimitException = RateLimitException::fromHeaders([
    'X-RateLimit-Limit' => '1000',
    'X-RateLimit-Remaining' => '0',
    'Retry-After' => '300'
], 'Custom rate limit message');
```

### ServerException (5xx)

Thrown when server errors occur (status codes 500-599).

```php
use CardTechie\TradingCardApiSdk\Exceptions\ServerException;

try {
    $cards = $api->card()->all();
} catch (ServerException $e) {
    $statusCode = $e->getHttpStatusCode();
    
    switch ($statusCode) {
        case 500:
            Log::error('API server error', $e->toArray());
            break;
        case 502:
            Log::warning('API gateway error', $e->toArray());
            // Maybe retry after delay
            break;
        case 503:
            Log::info('API temporarily unavailable', $e->toArray());
            // Retry with exponential backoff
            break;
    }
    
    // Show user-friendly message
    return response()->json(['error' => 'Service temporarily unavailable'], 503);
}

// Static factory methods
$internalError = ServerException::internalServerError();
$unavailable = ServerException::serviceUnavailable();
$badGateway = ServerException::badGateway();
$timeout = ServerException::gatewayTimeout();
```

### NetworkException

Thrown when network-level issues occur (timeouts, connection failures, DNS issues).

```php
use CardTechie\TradingCardApiSdk\Exceptions\NetworkException;

try {
    $cards = $api->card()->all();
} catch (NetworkException $e) {
    $errorCode = $e->getApiErrorCode();
    
    switch ($errorCode) {
        case 'connection_timeout':
            Log::warning('API connection timeout', $e->toArray());
            // Maybe retry with longer timeout
            break;
        case 'dns_resolution_failed':
            Log::error('Cannot resolve API hostname', $e->toArray());
            // Check network configuration
            break;
        case 'ssl_error':
            Log::error('SSL certificate error', $e->toArray());
            // Check SSL configuration
            break;
    }
    
    return response()->json(['error' => 'Network error'], 500);
}

// Static factory methods
$connectionTimeout = NetworkException::connectionTimeout(30.0);
$requestTimeout = NetworkException::requestTimeout(60.0);
$dnsFailure = NetworkException::dnsResolutionFailed('api.example.com');
$connectionRefused = NetworkException::connectionRefused('api.example.com', 443);
$sslError = NetworkException::sslError('Certificate verification failed');
```

## Error Handling Strategies

### 1. Catch Specific Exceptions

Handle different error types with specific logic:

```php
try {
    $card = $api->card()->find($cardId);
} catch (CardNotFoundException $e) {
    return response()->json(['error' => 'Card not found'], 404);
} catch (AuthenticationException $e) {
    // Clear cached token and redirect to login
    Cache::forget('tcapi_token');
    return redirect()->route('login');
} catch (RateLimitException $e) {
    return response()->json([
        'error' => 'Rate limit exceeded',
        'retry_after' => $e->getSecondsUntilReset()
    ], 429);
} catch (NetworkException $e) {
    Log::error('Network error accessing API', $e->toArray());
    return response()->json(['error' => 'Service unavailable'], 503);
} catch (TradingCardApiException $e) {
    // Handle any other API exception
    Log::error('API error', $e->toArray());
    return response()->json(['error' => 'API error occurred'], 500);
}
```

### 2. Retry Logic with Exponential Backoff

```php
use Illuminate\Support\Facades\Cache;

function makeApiRequestWithRetry($callable, $maxRetries = 3) {
    $attempt = 0;
    
    while ($attempt < $maxRetries) {
        try {
            return $callable();
        } catch (RateLimitException $e) {
            // Wait for rate limit reset
            sleep($e->getSecondsUntilReset());
            continue;
        } catch (ServerException $e) {
            if ($e->getHttpStatusCode() >= 500 && $attempt < $maxRetries - 1) {
                // Exponential backoff for server errors
                sleep(pow(2, $attempt));
                $attempt++;
                continue;
            }
            throw $e;
        } catch (NetworkException $e) {
            if ($attempt < $maxRetries - 1) {
                // Linear backoff for network errors
                sleep($attempt + 1);
                $attempt++;
                continue;
            }
            throw $e;
        }
    }
}

// Usage
$cards = makeApiRequestWithRetry(fn() => $api->card()->all());
```

### 3. Global Exception Handler

Register a global handler in your Laravel application:

```php
// In App\Exceptions\Handler.php

use CardTechie\TradingCardApiSdk\Exceptions\TradingCardApiException;
use CardTechie\TradingCardApiSdk\Exceptions\AuthenticationException;
use CardTechie\TradingCardApiSdk\Exceptions\RateLimitException;

public function register()
{
    $this->reportable(function (TradingCardApiException $e) {
        Log::error('Trading Card API Exception', [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'api_error_code' => $e->getApiErrorCode(),
            'http_status' => $e->getHttpStatusCode(),
            'context' => $e->getContext()
        ]);
    });

    $this->renderable(function (AuthenticationException $e) {
        return response()->json([
            'error' => 'Authentication failed',
            'message' => 'Please check your API credentials'
        ], 401);
    });

    $this->renderable(function (RateLimitException $e) {
        return response()->json([
            'error' => 'Rate limit exceeded',
            'retry_after' => $e->getSecondsUntilReset()
        ], 429);
    });
}
```

### 4. Circuit Breaker Pattern

Prevent cascading failures by temporarily disabling API calls:

```php
use Illuminate\Support\Facades\Cache;

class ApiCircuitBreaker
{
    private const FAILURE_THRESHOLD = 5;
    private const RECOVERY_TIMEOUT = 300; // 5 minutes
    private const CACHE_KEY = 'api_circuit_breaker';

    public function call(callable $callback)
    {
        $state = Cache::get(self::CACHE_KEY, ['failures' => 0, 'last_failure' => null]);
        
        // Check if circuit is open
        if ($this->isCircuitOpen($state)) {
            throw new \Exception('Circuit breaker is open');
        }
        
        try {
            $result = $callback();
            
            // Reset on success
            Cache::forget(self::CACHE_KEY);
            
            return $result;
        } catch (TradingCardApiException $e) {
            // Record failure
            $state['failures']++;
            $state['last_failure'] = now();
            Cache::put(self::CACHE_KEY, $state, self::RECOVERY_TIMEOUT * 2);
            
            throw $e;
        }
    }

    private function isCircuitOpen(array $state): bool
    {
        if ($state['failures'] < self::FAILURE_THRESHOLD) {
            return false;
        }
        
        if (!$state['last_failure']) {
            return false;
        }
        
        return now()->diffInSeconds($state['last_failure']) < self::RECOVERY_TIMEOUT;
    }
}

// Usage
$circuitBreaker = new ApiCircuitBreaker();
$cards = $circuitBreaker->call(fn() => $api->card()->all());
```

## Best Practices

1. **Catch Specific Exceptions**: Always catch the most specific exception types first
2. **Log Errors**: Use the `toArray()` method to get comprehensive error data for logging
3. **Provide Context**: Include relevant context when creating custom exceptions
4. **Handle Rate Limits**: Implement proper retry logic for rate-limited requests
5. **User-Friendly Messages**: Don't expose internal error details to end users
6. **Monitor Errors**: Track error rates and types to identify issues early
7. **Test Error Scenarios**: Write tests that cover various exception scenarios

## Configuration

The error handling system respects the same configuration as the validation system and doesn't require additional setup. All exceptions are automatically thrown when API errors occur.

For debugging, you can access the error parser instance:

```php
$errorParser = $api->card()->getErrorParser();
```

This comprehensive error handling system provides better debugging capabilities, structured error data, and enables robust error recovery strategies in your application.