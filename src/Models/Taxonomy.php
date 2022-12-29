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
     * @param Object $taxonomy
     * @param array $data
     */
    public static function build(Object $taxonomy, array $data) : Object;

    /**
     * Get the object from the API
     *
     * @param array $params
     */
    public static function getFromApi(array $params) : Object;
}
