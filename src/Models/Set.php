<?php

namespace CardTechie\TradingCardApiSdk\Models;

use Illuminate\Support\Collection;

/**
 * Class Set
 *
 * @property string $number_prefix
 * @property string $genre_id
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
            return collect($this->relationships['set-sources']);
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
            return collect($this->relationships['subsets']);
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
            return collect($this->relationships['checklist']);
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
     */
    // This is needed when we get the set list from the API
    public function setRelationships(array $relationships): void
    {
        parent::setRelationships($relationships);

        if (array_key_exists('genres', $this->relationships)) {
            // A set has one genre and one genre only
            $genreId = $this->genre_id;
            foreach ($this->relationships['genres'] as $index => $genre) {
                if ($genreId === $genre->id) {
                    $this->relationships['genre'] = $genre;
                    unset($this->relationships['genres']);
                }
            }
        }
    }
}
