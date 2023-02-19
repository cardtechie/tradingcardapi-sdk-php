<?php

namespace CardTechie\TradingCardApiSdk\Models;

/**
 * Taxonomy interface
 */
interface Taxonomy
{
    /**
     * Build the taxonomy object
     */
    public static function build(object $taxonomy, array $data): object;

    /**
     * Get the object from the API
     */
    public static function getFromApi(array $params): object;
}
