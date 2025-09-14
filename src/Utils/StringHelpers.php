<?php

namespace CardTechie\TradingCardApiSdk\Utils;

/**
 * String utility helper class
 */
class StringHelpers
{
    /**
     * Check if a string is a valid UUID format
     *
     * @param  string|null  $uuid  The string to validate
     * @return bool True if valid UUID format, false otherwise
     */
    public static function isValidUuid(?string $uuid): bool
    {
        if ($uuid === null || $uuid === '') {
            return false;
        }

        // General UUID pattern: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
        // Where x is any hex digit - this accepts all UUID versions
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

        return preg_match($pattern, $uuid) === 1;
    }

    /**
     * Clean and normalize a name string for comparison
     *
     * @param  string|null  $name  The name to normalize
     * @return string The normalized name
     */
    public static function normalizeName(?string $name): string
    {
        if ($name === null) {
            return '';
        }

        return trim(strtolower($name));
    }

    /**
     * Check if two names match after normalization
     *
     * @param  string|null  $name1  First name to compare
     * @param  string|null  $name2  Second name to compare
     * @return bool True if names match, false otherwise
     */
    public static function namesMatch(?string $name1, ?string $name2): bool
    {
        return self::normalizeName($name1) === self::normalizeName($name2);
    }
}
