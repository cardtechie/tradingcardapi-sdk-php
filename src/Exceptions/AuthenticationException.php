<?php

namespace CardTechie\TradingCardApiSdk\Exceptions;

/**
 * Exception thrown when authentication fails (401 Unauthorized)
 */
class AuthenticationException extends TradingCardApiException
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
        string $message = 'Authentication failed',
        int $code = 401,
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
            401,
            $context
        );
    }

    /**
     * Create exception for invalid credentials
     */
    public static function invalidCredentials(array $context = []): self
    {
        return new self(
            'Invalid client credentials provided',
            401,
            null,
            'invalid_credentials',
            [['title' => 'Invalid Credentials', 'detail' => 'The client credentials are invalid']],
            $context
        );
    }

    /**
     * Create exception for expired token
     */
    public static function expiredToken(array $context = []): self
    {
        return new self(
            'Access token has expired',
            401,
            null,
            'token_expired',
            [['title' => 'Token Expired', 'detail' => 'The access token has expired and needs to be refreshed']],
            $context
        );
    }
}
