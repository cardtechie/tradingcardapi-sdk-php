<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Models;

/**
 * Taxonomy interface
 */
interface Taxonomy
{
    /**
     * Build the taxonomy object
     *
     * @param  array<string, mixed>  $data
     */
    public static function build(object $taxonomy, array $data): object;

    /**
     * Get the object from the API
     *
     * @param  array<string, mixed>  $params
     */
    public static function getFromApi(array $params): object;
}
