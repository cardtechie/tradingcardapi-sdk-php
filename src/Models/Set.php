<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Models;

use Illuminate\Support\Collection;

/**
 * Class Set
 *
 * Represents a trading card set in the Trading Card API.
 *
 * @property string $id Set UUID
 * @property string|null $name Set name
 * @property string|null $description Set description
 * @property string|null $number_prefix Card-number prefix for this set
 * @property string|null $prefix Card-number prefix (API attribute alias)
 * @property string|null $genre_id Related genre UUID
 * @property string|null $parent_set_id Parent set UUID (if this is a subset)
 * @property string|null $brand Brand name
 * @property string|null $manufacturer Manufacturer name
 * @property string|null $series Set series
 * @property int|null $year Set year
 * @property string|null $release_date Release date
 * @property string|null $image Image URL
 * @property string|null $image_thumbnail Thumbnail image URL
 * @property int|null $card_count Number of cards in the set
 * @property bool|null $is_subset Whether the set is a subset
 * @property bool|null $is_variation Whether the set is a variation
 * @property int|null $serial Serial-number suffix for the set (e.g. /10, 1/1)
 * @property string|null $created_at Creation timestamp
 * @property string|null $updated_at Last update timestamp
 * @property-read int $current_card_count Number of checklist cards currently loaded (computed)
 */
class Set extends Model
{
    /**
     * Retrieve the genre of the set.
     */
    public function genre(): ?Genre
    {
        return $this->getRelationship('genre');
    }

    /**
     * Retrieve the parent set.
     */
    public function parent(): ?Set
    {
        return $this->getRelationship('parentset');
    }

    /**
     * Retrieve the manufacturer of the set.
     */
    public function manufacturer(): ?Manufacturer
    {
        return $this->getRelationship('manufacturers');
    }

    /**
     * Retrieve the brand of the set.
     */
    public function brand(): ?Brand
    {
        return $this->getRelationship('brands');
    }

    /**
     * Retrieve the year of the set.
     */
    public function year(): ?Year
    {
        return $this->getRelationship('years');
    }

    /**
     * Retrieve collection of set sources.
     *
     * @return Collection<int, SetSource>
     */
    public function sources(): Collection
    {
        if (array_key_exists('set-sources', $this->relationships)) {
            /** @var iterable<int, SetSource> $items */
            $items = $this->relationships['set-sources'];

            return collect($items);
        }

        return collect([]);
    }

    /**
     * Check if set has any sources.
     */
    public function hasSources(): bool
    {
        return $this->sources()->isNotEmpty();
    }

    /**
     * Get the checklist source for the set.
     */
    public function getChecklistSource(): ?SetSource
    {
        return $this->sources()
            ->firstWhere('source_type', 'checklist');
    }

    /**
     * Get the metadata source for the set.
     */
    public function getMetadataSource(): ?SetSource
    {
        return $this->sources()
            ->firstWhere('source_type', 'metadata');
    }

    /**
     * Get the images source for the set.
     */
    public function getImagesSource(): ?SetSource
    {
        return $this->sources()
            ->firstWhere('source_type', 'images');
    }

    /**
     * Retrieve collection of subsets of the set.
     *
     * @return Collection<int, Set>
     */
    public function subsets(): Collection
    {
        if (array_key_exists('subsets', $this->relationships)) {
            /** @var iterable<int, Set> $items */
            $items = $this->relationships['subsets'];

            return collect($items);
        }

        return collect([]);
    }

    /**
     * Check if set has any subsets.
     */
    public function hasSubsets(): bool
    {
        return $this->subsets()->isNotEmpty();
    }

    /**
     * Retrieve collection of checklist cards in the set.
     *
     * @return Collection<int, Card>
     */
    public function checklist(): Collection
    {
        if (array_key_exists('checklist', $this->relationships)) {
            /** @var iterable<int, Card> $items */
            $items = $this->relationships['checklist'];

            return collect($items);
        }

        return collect([]);
    }

    /**
     * Check if set has any checklist cards.
     */
    public function hasChecklist(): bool
    {
        return $this->checklist()->isNotEmpty();
    }

    /**
     * Return how many cards are in the set currently.
     */
    public function getCurrentCardCountAttribute(): int
    {
        return $this->checklist()->count();
    }

    /**
     * Get the index of the current card in the checklist.
     */
    private function setIndexInChecklist(Card $currentCard): int|false
    {
        return $this->checklist()->search(fn ($card) => $card->id === $currentCard->id);
    }

    /**
     * Retrieve the previous card of the card passed as an arg of the current set.
     */
    public function previousCard(Card $currentCard): ?Card
    {
        $index = $this->setIndexInChecklist($currentCard);

        if ($index === false || $index === 0) {
            return null;
        }

        return $this->checklist()->get($index - 1);
    }

    /**
     * Retrieve the next card of the card passed as an arg of the current set.
     */
    public function nextCard(Card $currentCard): ?Card
    {
        $index = $this->setIndexInChecklist($currentCard);

        if ($index === false) {
            return null;
        }

        return $this->checklist()->get($index + 1);
    }

    /**
     * Set the relationships for the object
     *
     * @param  array<string, mixed>  $relationships
     */
    // This is needed when we get the set list from the API
    public function setRelationships(array $relationships): void
    {
        parent::setRelationships($relationships);

        if (array_key_exists('genres', $this->relationships)) {
            // A set has one genre and one genre only. The flat genre_id attribute was
            // removed from the Set API response in tradingcardapi-api#1491, so match the
            // included genre via the JSON:API relationships linkage (relationships.genre.data.id)
            // instead. A missing linkage simply no-ops the match rather than mis-matching.
            $genreId = $this->linkage['genre']['id'] ?? null;
            if ($genreId !== null) {
                foreach ($this->relationships['genres'] as $genre) {
                    if ($genreId === $genre->id) {
                        $this->relationships['genre'] = $genre;
                        unset($this->relationships['genres']);

                        break;
                    }
                }
            }
        }
    }
}
