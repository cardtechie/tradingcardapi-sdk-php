<?php

namespace CardTechie\TradingCardApiSdk\Models;

/**
 * Taxonomy interface
 */
interface Taxonomy
{
    /**
     * Build the taxonomy object
     *
     * @param  object  $taxonomy
     * @param  array  $data
     */
    public static function build(object $taxonomy, array $data): object;

    /**
     * Get the object from the API
     *
     * @param  array  $params
     */
    public static function getFromApi(array $params): object;
}
