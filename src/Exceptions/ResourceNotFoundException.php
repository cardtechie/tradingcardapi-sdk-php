<?php

namespace CardTechie\TradingCardApiSdk\Exceptions;

/**
 * Exception thrown when a requested resource is not found (404 Not Found)
 */
class ResourceNotFoundException extends TradingCardApiException
{
    /**
     * Constructor
     *
     * @param  string  $message  The exception message
     * @param  int  $code  The exception code
     * @param  \Exception|null  $previous  The previous exception
     * @param  string|null  $apiErrorCode  The API error code
     * @param  array  $apiErrors  The API errors array
     * @param  array  $context  Additional context for debugging
     */
    public function __construct(
        string $message = 'Resource not found',
        int $code = 404,
        ?\Exception $previous = null,
        ?string $apiErrorCode = null,
        array $apiErrors = [],
        array $context = []
    ) {
        parent::__construct(
            $message,
            $code,
            $previous,
            $apiErrorCode,
            $apiErrors,
            404,
            $context
        );
    }

    /**
     * Create exception for specific resource type and ID
     *
     * @param  array<string, mixed>  $context
     */
    public static function resource(string $resourceType, string $resourceId, array $context = []): self
    {
        $message = "The {$resourceType} with ID '{$resourceId}' was not found";

        return new self(
            $message,
            404,
            null,
            'resource_not_found',
            [[
                'title' => 'Resource Not Found',
                'detail' => $message,
                'source' => ['parameter' => 'id'],
            ]],
            array_merge($context, [
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
            ])
        );
    }

    /**
     * Create exception for endpoint not found
     *
     * @param  array<string, mixed>  $context
     */
    public static function endpoint(string $endpoint, array $context = []): self
    {
        return new self(
            "The endpoint '{$endpoint}' was not found",
            404,
            null,
            'endpoint_not_found',
            [[
                'title' => 'Endpoint Not Found',
                'detail' => 'The requested endpoint does not exist',
            ]],
            array_merge($context, ['endpoint' => $endpoint])
        );
    }
}
