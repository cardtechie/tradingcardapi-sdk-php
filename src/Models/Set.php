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
    private int $checklistIndex = 0;

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
     * Retrieve the subsets of the set.
     */
    public function subsets(): array
    {
        if (array_key_exists('subsets', $this->relationships)) {
            return $this->relationships['subsets'];
        }

        return [];
    }

    /**
     * Retrieve the checklist of the set.
     */
    public function checklist(): array
    {
        if (array_key_exists('checklist', $this->relationships)) {
            return $this->relationships['checklist'];
        }

        return [];
    }

    /**
     * Return how many cards are in the set currently.
     */
    public function getCurrentCardCountAttribute(): int
    {
        return count($this->checklist());
    }

    /**
     * Get the index of the current card in the checklist and save as a class prop
     */
    private function setIndexInChecklist(Card $currentCard): void
    {
        foreach ($this->relationships['checklist'] as $index => $card) {
            if ($currentCard->id === $card->id) {
                $this->checklistIndex = $index;
            }
        }
    }

    /**
     * Retrieve the previous card of the card passed as an arg of the current set.
     */
    public function previousCard(Card $currentCard): ?Card
    {
        if (! $this->checklistIndex) {
            $this->setIndexInChecklist($currentCard);
        }

        $index = $this->checklistIndex - 1;
        if ($index >= 0) {
            return $this->relationships['checklist'][$index];
        }

        return null;
    }

    /**
     * Retrieve the next card of the card passed as an arg of the current set.
     */
    public function nextCard(Card $currentCard): ?Card
    {
        if (! $this->checklistIndex) {
            $this->setIndexInChecklist($currentCard);
        }

        $index = $this->checklistIndex + 1;
        if ($index < count($this->relationships['checklist'])) {
            return $this->relationships['checklist'][$index];
        }

        return null;
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
