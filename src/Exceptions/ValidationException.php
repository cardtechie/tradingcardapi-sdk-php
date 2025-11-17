<?php

namespace CardTechie\TradingCardApiSdk\Exceptions;

/**
 * Exception thrown when request validation fails (422 Unprocessable Entity)
 */
class ValidationException extends TradingCardApiException
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
        string $message = 'Validation failed',
        int $code = 422,
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
            422,
            $context
        );
    }

    /**
     * Get validation errors by field
     */
    public function getValidationErrors(): array
    {
        $errors = [];

        foreach ($this->apiErrors as $error) {
            if (isset($error['source']['parameter'])) {
                $field = $error['source']['parameter'];
                $errors[$field][] = $error['detail'] ?? $error['title'] ?? 'Validation error';
            } else {
                $errors['general'][] = $error['detail'] ?? $error['title'] ?? 'Validation error';
            }
        }

        return $errors;
    }

    /**
     * Check if a specific field has validation errors
     */
    public function hasFieldError(string $field): bool
    {
        $errors = $this->getValidationErrors();

        return isset($errors[$field]);
    }

    /**
     * Get validation errors for a specific field
     */
    public function getFieldErrors(string $field): array
    {
        $errors = $this->getValidationErrors();

        return $errors[$field] ?? [];
    }

    /**
     * Create exception for missing required fields
     *
     * @param  array<int, string>  $fields
     * @param  array<string, mixed>  $context
     */
    public static function missingRequiredFields(array $fields, array $context = []): self
    {
        $apiErrors = [];
        foreach ($fields as $field) {
            $apiErrors[] = [
                'title' => 'Required Field Missing',
                'detail' => "The {$field} field is required",
                'source' => ['parameter' => $field],
            ];
        }

        return new self(
            'Required fields are missing: '.implode(', ', $fields),
            422,
            null,
            'validation_failed',
            $apiErrors,
            $context
        );
    }

    /**
     * Create exception for invalid field values
     *
     * @param  array<string, string>  $fieldErrors
     * @param  array<string, mixed>  $context
     */
    public static function invalidFieldValues(array $fieldErrors, array $context = []): self
    {
        $apiErrors = [];
        foreach ($fieldErrors as $field => $errorMessage) {
            $apiErrors[] = [
                'title' => 'Invalid Field Value',
                'detail' => $errorMessage,
                'source' => ['parameter' => $field],
            ];
        }

        return new self(
            'Validation failed for provided data',
            422,
            null,
            'validation_failed',
            $apiErrors,
            $context
        );
    }
}
