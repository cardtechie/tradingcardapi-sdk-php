<?php

namespace CardTechie\TradingCardApiSdk\Models;

/**
 * Class Attribute
 */
class Attribute extends Model
{
    const OBJECT_TYPES = [
        'card',
        'set',
    ];

    const DATA_TYPES = [
        'boolean',
        'integer',
        'string',
    ];
}
