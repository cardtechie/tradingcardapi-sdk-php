<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Models\Traits;

/**
 * Trait OnCardable
 */
trait OnCardable
{
    /**
     * Return the onCardable configuration array for this model.
     *
     * @return array<string, mixed>
     */
    abstract public function onCardable(): array;

    /**
     * Prepare the on card relationships and return the object that matches the passed in data.
     *
     * @param  array<string, mixed>  $data
     */
    abstract public static function prepare($data): ?object;
}
