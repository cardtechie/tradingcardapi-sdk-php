<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Models;

/**
 * Class Oncard
 *
 * Represents a polymorphic on-card pivot linking a card to a player, team, or playerteam.
 *
 * @property string $id On-card UUID
 * @property string|null $on_cardable_id UUID of the related object
 * @property string|null $on_cardable_type Type of the related object (player|team|playerteam)
 * @property string|null $created_at Creation timestamp
 * @property string|null $updated_at Last update timestamp
 */
class Oncard extends Model {}
