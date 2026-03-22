<?php

namespace CardTechie\TradingCardApiSdk\Exceptions;

/**
 * Exception thrown when rate limit is exceeded (429 Too Many Requests)
 */
class RateLimitException extends TradingCardApiException
{
    /**
     * The rate limit quota
     *
     * @var int|null
     */
    protected $rateLimit;

    /**
     * The number of requests remaining
     *
     * @var int|null
     */
    protected $rateLimitRemaining;

    /**
     * The timestamp when the rate limit resets
     *
     * @var int|null
     */
    protected $rateLimitReset;

    /**
     * The number of seconds to wait before retrying
     *
     * @var int|null
     */
    protected $retryAfter;

    /**
     * Constructor
     *
     * @param  string  $message  The exception message
     * @param  int  $code  The exception code
     * @param  \Exception|null  $previous  The previous exception
     * @param  string|null  $apiErrorCode  The API error code
     * @param  array  $apiErrors  The API errors array
     * @param  array  $context  Additional context for debugging
     * @param  int|null  $rateLimit  The rate limit quota
     * @param  int|null  $rateLimitRemaining  The number of requests remaining
     * @param  int|null  $rateLimitReset  The timestamp when the rate limit resets
     * @param  int|null  $retryAfter  The number of seconds to wait before retrying
     */
    public function __construct(
        string $message = 'Rate limit exceeded',
        int $code = 429,
        ?\Exception $previous = null,
        ?string $apiErrorCode = null,
        array $apiErrors = [],
        array $context = [],
        ?int $rateLimit = null,
        ?int $rateLimitRemaining = null,
        ?int $rateLimitReset = null,
        ?int $retryAfter = null
    ) {
        parent::__construct(
            $message,
            $code,
            $previous,
            $apiErrorCode,
            $apiErrors,
            429,
            $context
        );

        $this->rateLimit = $rateLimit;
        $this->rateLimitRemaining = $rateLimitRemaining;
        $this->rateLimitReset = $rateLimitReset;
        $this->retryAfter = $retryAfter;
    }

    /**
     * Get the rate limit quota
     */
    public function getRateLimit(): ?int
    {
        return $this->rateLimit;
    }

    /**
     * Get the number of requests remaining
     */
    public function getRateLimitRemaining(): ?int
    {
        return $this->rateLimitRemaining;
    }

    /**
     * Get the timestamp when the rate limit resets
     */
    public function getRateLimitReset(): ?int
    {
        return $this->rateLimitReset;
    }

    /**
     * Get the number of seconds to wait before retrying
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    /**
     * Get the time when rate limit resets as DateTime
     */
    public function getRateLimitResetDateTime(): ?\DateTime
    {
        if ($this->rateLimitReset === null) {
            return null;
        }

        return (new \DateTime)->setTimestamp($this->rateLimitReset);
    }

    /**
     * Get seconds until rate limit reset
     */
    public function getSecondsUntilReset(): ?int
    {
        if ($this->rateLimitReset === null) {
            return $this->retryAfter;
        }

        return max(0, $this->rateLimitReset - time());
    }

    /**
     * Create exception from response headers
     *
     * @param  array<string, mixed>  $headers
     * @param  array<string, mixed>  $context
     */
    public static function fromHeaders(
        array $headers,
        string $message = 'Rate limit exceeded',
        array $context = []
    ): self {
        $rateLimit = isset($headers['X-RateLimit-Limit']) ? (int) $headers['X-RateLimit-Limit'] : null;
        $rateLimitRemaining = isset($headers['X-RateLimit-Remaining']) ? (int) $headers['X-RateLimit-Remaining'] : null;
        $rateLimitReset = isset($headers['X-RateLimit-Reset']) ? (int) $headers['X-RateLimit-Reset'] : null;
        $retryAfter = isset($headers['Retry-After']) ? (int) $headers['Retry-After'] : null;

        return new self(
            $message,
            429,
            null,
            'rate_limit_exceeded',
            [[
                'title' => 'Rate Limit Exceeded',
                'detail' => 'You have exceeded the API rate limit. Please wait before making more requests.',
            ]],
            $context,
            $rateLimit,
            $rateLimitRemaining,
            $rateLimitReset,
            $retryAfter
        );
    }

    /**
     * Convert exception to array including rate limit data
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'rate_limit' => $this->rateLimit,
            'rate_limit_remaining' => $this->rateLimitRemaining,
            'rate_limit_reset' => $this->rateLimitReset,
            'retry_after' => $this->retryAfter,
            'seconds_until_reset' => $this->getSecondsUntilReset(),
        ]);
    }
}
