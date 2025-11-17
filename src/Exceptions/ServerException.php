<?php

namespace CardTechie\TradingCardApiSdk\Exceptions;

/**
 * Exception thrown when a server error occurs (5xx)
 */
class ServerException extends TradingCardApiException
{
    /**
     * Constructor
     *
     * @param  string  $message  The exception message
     * @param  int  $code  The exception code
     * @param  \Exception|null  $previous  The previous exception
     * @param  string|null  $apiErrorCode  The API error code
     * @param  array  $apiErrors  The API errors array
     * @param  int|null  $httpStatusCode  The HTTP status code
     * @param  array  $context  Additional context for debugging
     */
    public function __construct(
        string $message = 'Internal server error',
        int $code = 500,
        ?\Exception $previous = null,
        ?string $apiErrorCode = null,
        array $apiErrors = [],
        ?int $httpStatusCode = 500,
        array $context = []
    ) {
        parent::__construct(
            $message,
            $code,
            $previous,
            $apiErrorCode,
            $apiErrors,
            $httpStatusCode,
            $context
        );
    }

    /**
     * Create exception for internal server error (500)
     *
     * @param  array<string, mixed>  $context
     */
    public static function internalServerError(array $context = []): self
    {
        return new self(
            'The server encountered an internal error',
            500,
            null,
            'internal_server_error',
            [[
                'title' => 'Internal Server Error',
                'detail' => 'The server encountered an unexpected condition that prevented it from fulfilling the request',
            ]],
            500,
            $context
        );
    }

    /**
     * Create exception for service unavailable (503)
     *
     * @param  array<string, mixed>  $context
     */
    public static function serviceUnavailable(array $context = []): self
    {
        return new self(
            'Service temporarily unavailable',
            503,
            null,
            'service_unavailable',
            [[
                'title' => 'Service Unavailable',
                'detail' => 'The service is temporarily unavailable due to maintenance or overload',
            ]],
            503,
            $context
        );
    }

    /**
     * Create exception for bad gateway (502)
     *
     * @param  array<string, mixed>  $context
     */
    public static function badGateway(array $context = []): self
    {
        return new self(
            'Bad gateway response',
            502,
            null,
            'bad_gateway',
            [[
                'title' => 'Bad Gateway',
                'detail' => 'The server received an invalid response from the upstream server',
            ]],
            502,
            $context
        );
    }

    /**
     * Create exception for gateway timeout (504)
     *
     * @param  array<string, mixed>  $context
     */
    public static function gatewayTimeout(array $context = []): self
    {
        return new self(
            'Gateway timeout',
            504,
            null,
            'gateway_timeout',
            [[
                'title' => 'Gateway Timeout',
                'detail' => 'The server did not receive a timely response from the upstream server',
            ]],
            504,
            $context
        );
    }
}
