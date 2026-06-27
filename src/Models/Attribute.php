<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Models;

/**
 * Class Attribute
 *
 * Represents a reusable attribute definition in the Trading Card API.
 *
 * @property string $id Attribute UUID
 * @property string|null $name Attribute name
 * @property string|null $value Attribute value
 * @property string|null $type Data type (boolean|integer|string)
 * @property string|null $description Attribute description
 * @property bool|null $is_searchable Whether the attribute is searchable
 * @property bool|null $is_filterable Whether the attribute is filterable
 * @property int|null $sort_order Sort order
 * @property string|null $created_at Creation timestamp
 * @property string|null $updated_at Last update timestamp
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
