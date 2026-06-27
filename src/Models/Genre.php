<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Models;

/**
 * Class Genre
 *
 * Represents a card genre in the Trading Card API.
 *
 * @property string $id Genre UUID
 * @property string|null $name Genre name
 * @property string|null $slug Genre slug
 * @property string|null $description Genre description
 * @property int|null $sort_order Sort order
 * @property bool|null $is_active Whether the genre is active
 * @property string|null $created_at Creation timestamp
 * @property string|null $updated_at Last update timestamp
 * @property string|null $deleted_at Soft-deletion timestamp
 */
class Genre extends Model {}
