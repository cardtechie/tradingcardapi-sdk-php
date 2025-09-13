<?php

namespace CardTechie\TradingCardApiSdk\Models;

class ObjectAttribute extends Model
{
    public function cards(): array
    {
        $relationships = $this->getRelationships();

        return $relationships['cards'] ?? [];
    }
}
