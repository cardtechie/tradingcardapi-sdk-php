<?php

namespace CardTechie\TradingCardApiSdk\Services;

/**
 * Central redaction helper for sensitive credential material.
 *
 * Failed requests and OAuth token fetches serialize headers, response bodies,
 * and parsed payloads into exception context (which integrators routinely dump
 * to logs). This helper strips credential material — Authorization headers,
 * bearer tokens, client_secret, access_token, refresh_token, and passwords —
 * before any of it is stored in exception context or written to logs.
 */
class Redactor
{
    /**
     * Sentinel that replaces any redacted value.
     */
    public const REDACTED = '[REDACTED]';

    /**
     * Header names whose values must always be redacted (case-insensitive).
     *
     * @var string[]
     */
    private const SENSITIVE_HEADERS = [
        'authorization',
        'proxy-authorization',
        'cookie',
        'set-cookie',
        'x-api-key',
    ];

    /**
     * Payload keys whose values must always be redacted at any nesting depth
     * (case-insensitive).
     *
     * @var string[]
     */
    private const SENSITIVE_KEYS = [
        'client_secret',
        'access_token',
        'refresh_token',
        'id_token',
        'password',
        'secret',
        'authorization',
    ];

    /**
     * Matches bearer tokens in free-text strings, e.g. "Bearer eyJ0eXAi…".
     */
    private const BEARER_PATTERN = '/\bBearer\s+[A-Za-z0-9\-._~+\/]+=*/i';

    /**
     * Redact a flat headers map. Any header whose name matches the
     * sensitive-header deny list (case-insensitive) has its value replaced
     * with the redaction sentinel; all other headers pass through untouched.
     *
     * @param  array<string, mixed>  $headers
     * @return array<string, mixed>
     */
    public function redactHeaders(array $headers): array
    {
        $redacted = [];
        foreach ($headers as $name => $value) {
            if (in_array(strtolower((string) $name), self::SENSITIVE_HEADERS, true)) {
                $redacted[$name] = self::REDACTED;
            } else {
                $redacted[$name] = $value;
            }
        }

        return $redacted;
    }

    /**
     * Recursively redact an arbitrary array payload by key. Any key matching
     * the sensitive-key deny list (case-insensitive) has its value replaced
     * with the redaction sentinel, regardless of nesting depth. Nested arrays
     * are walked; non-sensitive scalar values are left untouched.
     *
     * @param  array<mixed>  $payload
     * @return array<mixed>
     */
    public function redact(array $payload): array
    {
        $redacted = [];
        foreach ($payload as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::SENSITIVE_KEYS, true)) {
                $redacted[$key] = self::REDACTED;

                continue;
            }

            if (is_array($value)) {
                $redacted[$key] = $this->redact($value);

                continue;
            }

            if (is_string($value)) {
                $redacted[$key] = $this->maskBearerTokens($value);

                continue;
            }

            $redacted[$key] = $value;
        }

        return $redacted;
    }

    /**
     * Mask a raw body string before it is stored in exception context.
     *
     * When the body is valid JSON, it is decoded, recursively key-redacted, and
     * re-serialized so credential values keyed by client_secret/access_token/etc.
     * never appear verbatim. Non-JSON bodies have any embedded bearer tokens
     * masked but are otherwise preserved so they stay useful for debugging
     * non-credential errors.
     */
    public function redactBody(?string $body): ?string
    {
        if ($body === null || $body === '') {
            return $body;
        }

        $decoded = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $encoded = json_encode($this->redact($decoded));
            if ($encoded !== false) {
                return $encoded;
            }
        }

        return $this->maskBearerTokens($body);
    }

    /**
     * Replace any bearer token occurrences in a free-text string with the
     * redaction sentinel.
     */
    private function maskBearerTokens(string $value): string
    {
        return (string) preg_replace(self::BEARER_PATTERN, 'Bearer '.self::REDACTED, $value);
    }
}
