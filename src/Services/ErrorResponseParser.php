<?php

namespace CardTechie\TradingCardApiSdk\Services;

use CardTechie\TradingCardApiSdk\Exceptions\AuthenticationException;
use CardTechie\TradingCardApiSdk\Exceptions\AuthorizationException;
use CardTechie\TradingCardApiSdk\Exceptions\NetworkException;
use CardTechie\TradingCardApiSdk\Exceptions\RateLimitException;
use CardTechie\TradingCardApiSdk\Exceptions\ResourceNotFoundException;
use CardTechie\TradingCardApiSdk\Exceptions\ServerException;
use CardTechie\TradingCardApiSdk\Exceptions\TradingCardApiException;
use CardTechie\TradingCardApiSdk\Exceptions\ValidationException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 * Service for parsing API error responses and creating appropriate exception instances
 */
class ErrorResponseParser
{
    /**
     * Parse a Guzzle exception and create appropriate Trading Card API exception
     */
    public function parseGuzzleException(\Exception $exception): TradingCardApiException
    {
        // Handle connection/network exceptions
        if ($exception instanceof ConnectException) {
            return NetworkException::fromGuzzleConnectException($exception, [
                'original_exception' => get_class($exception),
                'original_message' => $exception->getMessage(),
            ]);
        }

        // Handle HTTP exceptions with response
        if ($exception instanceof RequestException && $exception->hasResponse()) {
            $response = $exception->getResponse();

            return $this->parseHttpResponse($response, $exception);
        }

        // Handle other Guzzle exceptions
        if ($exception instanceof RequestException) {
            return new NetworkException(
                'HTTP request failed: '.$exception->getMessage(),
                0,
                $exception,
                'request_failed',
                [['title' => 'Request Failed', 'detail' => $exception->getMessage()]],
                [
                    'original_exception' => get_class($exception),
                    'original_message' => $exception->getMessage(),
                ]
            );
        }

        // Generic exception handling
        return new TradingCardApiException(
            'Unexpected error: '.$exception->getMessage(),
            0,
            $exception,
            'unexpected_error',
            [['title' => 'Unexpected Error', 'detail' => $exception->getMessage()]],
            null,
            [
                'original_exception' => get_class($exception),
                'original_message' => $exception->getMessage(),
            ]
        );
    }

    /**
     * Parse HTTP response and create appropriate exception
     */
    public function parseHttpResponse(ResponseInterface $response, ?\Exception $previous = null): TradingCardApiException
    {
        $statusCode = $response->getStatusCode();
        $headers = $this->parseHeaders($response->getHeaders());
        $body = (string) $response->getBody();
        $responseData = $this->parseResponseBody($body);

        $context = [
            'http_status_code' => $statusCode,
            'headers' => $headers,
            'response_body' => $body,
            'parsed_response' => $responseData,
        ];

        // Extract error information from response
        $apiErrorCode = $responseData['error'] ?? null;
        $apiErrors = $this->extractApiErrors($responseData);
        $message = $this->extractErrorMessage($responseData, $statusCode);

        // Create appropriate exception based on status code
        switch ($statusCode) {
            case 401:
                return new AuthenticationException(
                    $message,
                    401,
                    $previous,
                    $apiErrorCode,
                    $apiErrors,
                    $context
                );

            case 403:
                return new AuthorizationException(
                    $message,
                    403,
                    $previous,
                    $apiErrorCode,
                    $apiErrors,
                    $context
                );

            case 404:
                return new ResourceNotFoundException(
                    $message,
                    404,
                    $previous,
                    $apiErrorCode,
                    $apiErrors,
                    $context
                );

            case 422:
                return new ValidationException(
                    $message,
                    422,
                    $previous,
                    $apiErrorCode,
                    $apiErrors,
                    $context
                );

            case 429:
                return RateLimitException::fromHeaders(
                    $headers,
                    $message,
                    $context
                );

            default:
                if ($statusCode >= 500) {
                    return new ServerException(
                        $message,
                        $statusCode,
                        $previous,
                        $apiErrorCode,
                        $apiErrors,
                        $statusCode,
                        $context
                    );
                } else {
                    return new TradingCardApiException(
                        $message,
                        $statusCode,
                        $previous,
                        $apiErrorCode,
                        $apiErrors,
                        $statusCode,
                        $context
                    );
                }
        }
    }

    /**
     * Parse response body to extract structured data
     */
    private function parseResponseBody(string $body): array
    {
        if (empty($body)) {
            return [];
        }

        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['raw_body' => $body];
        }

        return $decoded;
    }

    /**
     * Extract API errors from response data
     */
    private function extractApiErrors(array $responseData): array
    {
        if (isset($responseData['errors']) && is_array($responseData['errors'])) {
            // Check if this is Laravel validation format first (associative array with field names)
            $isLaravelFormat = false;
            foreach ($responseData['errors'] as $key => $value) {
                if (! is_numeric($key) && (is_array($value) || is_string($value))) {
                    $isLaravelFormat = true;
                    break;
                }
            }

            if ($isLaravelFormat) {
                // Laravel validation format
                $errors = [];
                foreach ($responseData['errors'] as $field => $fieldErrors) {
                    foreach ((array) $fieldErrors as $errorMessage) {
                        $errors[] = [
                            'title' => 'Validation Error',
                            'detail' => $errorMessage,
                            'source' => ['parameter' => $field],
                        ];
                    }
                }

                return $errors;
            } else {
                // JSON:API format (indexed array of error objects)
                return $responseData['errors'];
            }
        }

        // Simple message format
        if (isset($responseData['message'])) {
            return [['title' => 'Error', 'detail' => $responseData['message']]];
        }

        // Single error format
        if (isset($responseData['error'])) {
            // Use error_description if available, otherwise use error field
            $errorDetail = $responseData['error_description'] ?? $responseData['error'];

            return [['title' => 'Error', 'detail' => $errorDetail]];
        }

        return [];
    }

    /**
     * Extract error message from response data
     */
    private function extractErrorMessage(array $responseData, int $statusCode): string
    {
        // Try to get message from various possible locations
        if (isset($responseData['message'])) {
            return $responseData['message'];
        }

        if (isset($responseData['error_description'])) {
            return $responseData['error_description'];
        }

        if (isset($responseData['error']) && is_string($responseData['error'])) {
            return $responseData['error'];
        }

        // Get first error detail from errors array
        $apiErrors = $this->extractApiErrors($responseData);
        if (! empty($apiErrors) && isset($apiErrors[0]['detail'])) {
            return $apiErrors[0]['detail'];
        }

        // Default messages based on status code
        return match ($statusCode) {
            401 => 'Authentication failed',
            403 => 'Access forbidden',
            404 => 'Resource not found',
            422 => 'Validation failed',
            429 => 'Rate limit exceeded',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
            504 => 'Gateway timeout',
            default => "HTTP {$statusCode} error"
        };
    }

    /**
     * Parse headers from Guzzle format to flat array
     */
    private function parseHeaders(array $headers): array
    {
        $parsed = [];
        foreach ($headers as $name => $values) {
            $parsed[$name] = is_array($values) ? implode(', ', $values) : $values;
        }

        return $parsed;
    }

    /**
     * Create exception for specific resource not found
     */
    public function createResourceNotFoundException(string $resourceType, string $resourceId, array $context = []): ResourceNotFoundException
    {
        $exceptionClass = match ($resourceType) {
            'card' => \CardTechie\TradingCardApiSdk\Exceptions\CardNotFoundException::class,
            'player' => \CardTechie\TradingCardApiSdk\Exceptions\PlayerNotFoundException::class,
            'set' => \CardTechie\TradingCardApiSdk\Exceptions\SetNotFoundException::class,
            default => ResourceNotFoundException::class
        };

        if ($exceptionClass === ResourceNotFoundException::class) {
            return ResourceNotFoundException::resource($resourceType, $resourceId, $context);
        }

        return $exceptionClass::byId($resourceId, $context);
    }
}
