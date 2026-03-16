<?php

namespace CardTechie\TradingCardApiSdk\Exceptions;

/**
 * Exception thrown when a conflict is detected (409 Conflict)
 */
class ConflictException extends TradingCardApiException
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
        string $message = 'Conflict detected',
        int $code = 409,
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
            409,
            $context
        );
    }

    /**
     * Create exception for duplicate resource
     */
    public static function duplicate(string $resource = '', array $context = []): self
    {
        $message = $resource
            ? "Duplicate {$resource} detected"
            : 'Duplicate resource detected';

        return new self(
            $message,
            409,
            null,
            'duplicate_resource',
            [['title' => 'Conflict', 'detail' => $message]],
            $context
        );
    }
}
