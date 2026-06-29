<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Exceptions;

use Exception;

/**
 * Base exception class for all Trading Card API SDK exceptions
 */
class TradingCardApiException extends Exception
{
    /**
     * The API error code
     */
    protected ?string $apiErrorCode = null;

    /**
     * The API errors array
     *
     * @var array<int|string, mixed>
     */
    protected array $apiErrors = [];

    /**
     * The HTTP status code
     */
    protected ?int $httpStatusCode = null;

    /**
     * Additional context for debugging
     *
     * @var array<string, mixed>
     */
    protected array $context = [];

    /**
     * Constructor
     *
     * @param  string  $message  The exception message
     * @param  int  $code  The exception code
     * @param  Exception|null  $previous  The previous exception
     * @param  string|null  $apiErrorCode  The API error code
     * @param  array<int, array<string, mixed>>  $apiErrors  The API errors array
     * @param  int|null  $httpStatusCode  The HTTP status code
     * @param  array<string, mixed>  $context  Additional context for debugging
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        ?string $apiErrorCode = null,
        array $apiErrors = [],
        ?int $httpStatusCode = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);

        $this->apiErrorCode = $apiErrorCode;
        $this->apiErrors = $apiErrors;
        $this->httpStatusCode = $httpStatusCode;
        $this->context = $context;
    }

    /**
     * Get the API error code
     */
    public function getApiErrorCode(): ?string
    {
        return $this->apiErrorCode;
    }

    /**
     * Get the API errors array
     *
     * @return array<int|string, mixed>
     */
    public function getApiErrors(): array
    {
        return $this->apiErrors;
    }

    /**
     * Get the HTTP status code
     */
    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }

    /**
     * Get the context for debugging
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get the first API error message if available
     */
    public function getApiErrorMessage(): ?string
    {
        if (! empty($this->apiErrors) && is_array($this->apiErrors[0])) {
            return $this->apiErrors[0]['detail'] ?? $this->apiErrors[0]['title'] ?? null;
        }

        return ! empty($this->apiErrors) ? (string) $this->apiErrors[0] : null;
    }

    /**
     * Check if this is a client error (4xx)
     */
    public function isClientError(): bool
    {
        return $this->httpStatusCode >= 400 && $this->httpStatusCode < 500;
    }

    /**
     * Check if this is a server error (5xx)
     */
    public function isServerError(): bool
    {
        return $this->httpStatusCode >= 500 && $this->httpStatusCode < 600;
    }

    /**
     * Convert exception to array for logging/serialization
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'api_error_code' => $this->apiErrorCode,
            'api_errors' => $this->apiErrors,
            'http_status_code' => $this->httpStatusCode,
            'context' => $this->context,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
}
