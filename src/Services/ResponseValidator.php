<?php

namespace CardTechie\TradingCardApiSdk\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Service for validating API responses against expected schemas
 */
class ResponseValidator
{
    private array $errors = [];

    private bool $isValid = true;

    private array $config;

    /**
     * @var array Schema cache
     */
    private static array $schemaCache = [];

    public function __construct()
    {
        $this->config = config('tradingcardapi.validation', [
            'enabled' => true,
            'strict_mode' => false,
            'log_validation_errors' => true,
        ]);
    }

    /**
     * Validate API response against expected schema
     *
     * @param  string  $resourceType  The resource type (e.g., 'card', 'player')
     * @param  array  $data  The response data
     * @param  string  $endpoint  The API endpoint for context
     */
    public function validate(string $resourceType, array $data, string $endpoint = ''): bool
    {
        if (! $this->config['enabled']) {
            return true;
        }

        $this->resetValidation();

        try {
            // Detect if this is a collection response
            $isCollection = $this->isCollectionResponse($data);
            
            // Get schema for resource type
            $schema = $this->getSchema($resourceType, $isCollection);

            if (empty($schema)) {
                $this->logWarning("No schema defined for resource type: {$resourceType}");

                return true; // Don't fail if no schema is defined
            }

            // Validate the response structure
            $validator = Validator::make($data, $schema);

            if ($validator->fails()) {
                $this->isValid = false;
                $this->errors = $validator->errors()->toArray();

                $errorMessage = "API response validation failed for {$resourceType}";
                if ($endpoint) {
                    $errorMessage .= " (endpoint: {$endpoint})";
                }

                if ($this->config['log_validation_errors']) {
                    Log::warning($errorMessage, [
                        'resource_type' => $resourceType,
                        'endpoint' => $endpoint,
                        'errors' => $this->errors,
                        'data' => $data,
                        'is_collection' => $isCollection,
                    ]);
                }

                if ($this->config['strict_mode']) {
                    throw new ValidationException($validator);
                }
            }

        } catch (ValidationException $e) {
            throw $e; // Re-throw validation exceptions in strict mode
        } catch (\Exception $e) {
            $this->logError("Validation error for {$resourceType}: ".$e->getMessage());
            // Don't fail validation on internal errors unless in strict mode
            if ($this->config['strict_mode']) {
                throw $e;
            }
        }

        return $this->isValid;
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if the last validation was successful
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * Get schema for a resource type
     */
    private function getSchema(string $resourceType, bool $isCollection = false): array
    {
        // Create cache key that includes collection flag
        $cacheKey = $resourceType . ($isCollection ? '_collection' : '_single');
        
        // Check cache first
        if (isset(self::$schemaCache[$cacheKey])) {
            return self::$schemaCache[$cacheKey];
        }

        $schemaClass = $this->getSchemaClass($resourceType);

        if (! class_exists($schemaClass)) {
            return [];
        }

        $schemaInstance = new $schemaClass;
        
        // Get appropriate schema based on response type
        if ($isCollection && method_exists($schemaInstance, 'getCollectionRules')) {
            $schema = $schemaInstance->getCollectionRules();
        } else {
            $schema = $schemaInstance->getRules();
        }
        
        self::$schemaCache[$cacheKey] = $schema;

        return $schema;
    }

    /**
     * Get the schema class name for a resource type
     */
    private function getSchemaClass(string $resourceType): string
    {
        $className = ucfirst(str_replace(['-', '_'], '', ucwords($resourceType, '-_')));

        return "CardTechie\\TradingCardApiSdk\\Schemas\\{$className}Schema";
    }

    /**
     * Reset validation state
     */
    private function resetValidation(): void
    {
        $this->errors = [];
        $this->isValid = true;
    }

    /**
     * Log warning message
     */
    private function logWarning(string $message): void
    {
        if ($this->config['log_validation_errors']) {
            Log::warning("[TradingCardAPI SDK] {$message}");
        }
    }

    /**
     * Log error message
     */
    private function logError(string $message): void
    {
        if ($this->config['log_validation_errors']) {
            Log::error("[TradingCardAPI SDK] {$message}");
        }
    }

    /**
     * Detect if the response is a collection (array) or single object
     */
    private function isCollectionResponse(array $data): bool
    {
        // Check if the 'data' field exists and is an array of objects
        if (!isset($data['data'])) {
            return false;
        }
        
        // If data is an array and has multiple items, or first item is an object with id/type
        if (is_array($data['data'])) {
            // Empty array is still a collection
            if (empty($data['data'])) {
                return true;
            }
            
            // Check if first item looks like a resource object
            $firstItem = $data['data'][0] ?? null;
            if (is_array($firstItem) && isset($firstItem['type']) && isset($firstItem['id'])) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Clear schema cache (useful for testing)
     */
    public static function clearSchemaCache(): void
    {
        self::$schemaCache = [];
    }
}
