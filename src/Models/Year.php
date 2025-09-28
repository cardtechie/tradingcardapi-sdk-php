<?php

namespace CardTechie\TradingCardApiSdk\Models;

/**
 * Class Year
 */
class Year extends Model
{
    /**
     * Retrieve the sets associated with this year.
     */
    public function sets(): array
    {
        if (array_key_exists('sets', $this->relationships)) {
            return $this->relationships['sets'];
        }

        return [];
    }

    /**
     * Retrieve the parent year (if this year is a child/variant).
     */
    public function parent(): ?Year
    {
        if (array_key_exists('parent', $this->relationships) && ! empty($this->relationships['parent'])) {
            return $this->relationships['parent'][0] ?? null;
        }

        return null;
    }

    /**
     * Retrieve the child years (if this year has variants).
     */
    public function children(): array
    {
        if (array_key_exists('children', $this->relationships)) {
            return $this->relationships['children'];
        }

        return [];
    }

    /**
     * Check if this year has a parent year.
     */
    public function hasParent(): bool
    {
        return ! empty($this->parent_year);
    }

    /**
     * Check if this year has child years.
     */
    public function hasChildren(): bool
    {
        return count($this->children()) > 0;
    }

    /**
     * Get the display name for this year.
     * Prioritizes the 'name' field, falls back to 'year' or 'description'.
     */
    public function getDisplayName(): string
    {
        return $this->name
            ?? $this->year
            ?? $this->description
            ?? 'Unknown Year';
    }
}
