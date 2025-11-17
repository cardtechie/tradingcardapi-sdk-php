<?php

namespace CardTechie\TradingCardApiSdk\Exceptions;

/**
 * Exception thrown when user lacks permission for the requested resource (403 Forbidden)
 */
class AuthorizationException extends TradingCardApiException
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
        string $message = 'Access forbidden',
        int $code = 403,
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
            403,
            $context
        );
    }

    /**
     * Create exception for insufficient permissions
     *
     * @param  array<string, mixed>  $context
     */
    public static function insufficientPermissions(string $resource = '', array $context = []): self
    {
        $message = $resource
            ? "Insufficient permissions to access {$resource}"
            : 'Insufficient permissions';

        return new self(
            $message,
            403,
            null,
            'insufficient_permissions',
            [['title' => 'Insufficient Permissions', 'detail' => $message]],
            $context
        );
    }

    /**
     * Create exception for suspended account
     *
     * @param  array<string, mixed>  $context
     */
    public static function accountSuspended(array $context = []): self
    {
        return new self(
            'Account has been suspended',
            403,
            null,
            'account_suspended',
            [['title' => 'Account Suspended', 'detail' => 'Your account has been suspended']],
            $context
        );
    }
}
