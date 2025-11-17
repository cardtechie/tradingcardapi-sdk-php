<?php

use CardTechie\TradingCardApiSdk\Models\Brand;
use CardTechie\TradingCardApiSdk\Models\Card;
use CardTechie\TradingCardApiSdk\Models\Genre;
use CardTechie\TradingCardApiSdk\Models\Manufacturer;
use CardTechie\TradingCardApiSdk\Models\Set;
use CardTechie\TradingCardApiSdk\Models\Year;

it('can be instantiated with attributes', function () {
    $set = new Set(['id' => '123', 'name' => 'Test Set', 'number_prefix' => 'TS']);

    expect($set)->toBeInstanceOf(Set::class);
    expect($set->id)->toBe('123');
    expect($set->name)->toBe('Test Set');
    expect($set->number_prefix)->toBe('TS');
});

it('returns genre relationship', function () {
    $genre = new Genre(['id' => '1', 'name' => 'Test Genre']);
    $set = new Set(['id' => '123']);
    $set->setRelationships(['genre' => $genre]);

    expect($set->genre())->toBe($genre);
});

it('returns null when no genre relationship', function () {
    $set = new Set(['id' => '123']);

    expect($set->genre())->toBeNull();
});

it('returns parent set relationship', function () {
    $parentSet = new Set(['id' => '1', 'name' => 'Parent Set']);
    $set = new Set(['id' => '123']);
    $set->setRelationships(['parentset' => $parentSet]);

    expect($set->parent())->toBe($parentSet);
});

it('returns manufacturer relationship', function () {
    $manufacturer = new Manufacturer(['id' => '1', 'name' => 'Test Manufacturer']);
    $set = new Set(['id' => '123']);
    $set->setRelationships(['manufacturers' => $manufacturer]);

    expect($set->manufacturer())->toBe($manufacturer);
});

it('returns brand relationship', function () {
    $brand = new Brand(['id' => '1', 'name' => 'Test Brand']);
    $set = new Set(['id' => '123']);
    $set->setRelationships(['brands' => $brand]);

    expect($set->brand())->toBe($brand);
});

it('returns year relationship', function () {
    $year = new Year(['id' => '1', 'year' => '2023']);
    $set = new Set(['id' => '123']);
    $set->setRelationships(['years' => $year]);

    expect($set->year())->toBe($year);
});

it('returns subsets collection', function () {
    $subset1 = new Set(['id' => '1', 'name' => 'Subset 1']);
    $subset2 = new Set(['id' => '2', 'name' => 'Subset 2']);
    $subsets = [$subset1, $subset2];

    $set = new Set(['id' => '123']);
    $set->setRelationships(['subsets' => $subsets]);

    $subsetsCollection = $set->subsets();

    expect($subsetsCollection)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($subsetsCollection)->toHaveCount(2);
    expect($subsetsCollection->get(0))->toBe($subset1);
    expect($subsetsCollection->get(1))->toBe($subset2);
});

it('returns empty collection when no subsets', function () {
    $set = new Set(['id' => '123']);

    $subsets = $set->subsets();

    expect($subsets)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($subsets)->toBeEmpty();
});

it('hasSubsets returns false when no subsets', function () {
    $set = new Set(['id' => '123']);

    expect($set->hasSubsets())->toBeFalse();
});

it('hasSubsets returns true when subsets exist', function () {
    $subset1 = new Set(['id' => '1', 'name' => 'Subset 1']);

    $set = new Set(['id' => '123']);
    $set->setRelationships(['subsets' => [$subset1]]);

    expect($set->hasSubsets())->toBeTrue();
});

it('subsets collection supports collection methods', function () {
    $subset1 = new Set(['id' => '1', 'name' => 'Subset 1']);
    $subset2 = new Set(['id' => '2', 'name' => 'Subset 2']);
    $subset3 = new Set(['id' => '3', 'name' => 'Subset 3']);

    $set = new Set(['id' => '123']);
    $set->setRelationships(['subsets' => [$subset1, $subset2, $subset3]]);

    $subsets = $set->subsets();

    // Test pluck
    $names = $subsets->pluck('name');
    expect($names->toArray())->toBe(['Subset 1', 'Subset 2', 'Subset 3']);

    // Test filter
    $filteredSubsets = $subsets->filter(fn ($subset) => $subset->id === '2');
    expect($filteredSubsets)->toHaveCount(1);
    expect($filteredSubsets->first()->name)->toBe('Subset 2');

    // Test first
    expect($subsets->first()->name)->toBe('Subset 1');
});

it('returns checklist collection', function () {
    $card1 = new Card(['id' => '1', 'name' => 'Card 1']);
    $card2 = new Card(['id' => '2', 'name' => 'Card 2']);
    $checklist = [$card1, $card2];

    $set = new Set(['id' => '123']);
    $set->setRelationships(['checklist' => $checklist]);

    $checklistCollection = $set->checklist();

    expect($checklistCollection)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($checklistCollection)->toHaveCount(2);
    expect($checklistCollection->get(0))->toBe($card1);
    expect($checklistCollection->get(1))->toBe($card2);
});

it('returns empty collection when no checklist', function () {
    $set = new Set(['id' => '123']);

    $checklist = $set->checklist();

    expect($checklist)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($checklist)->toBeEmpty();
});

it('hasChecklist returns false when no checklist', function () {
    $set = new Set(['id' => '123']);

    expect($set->hasChecklist())->toBeFalse();
});

it('hasChecklist returns true when checklist exists', function () {
    $card1 = new Card(['id' => '1', 'name' => 'Card 1']);

    $set = new Set(['id' => '123']);
    $set->setRelationships(['checklist' => [$card1]]);

    expect($set->hasChecklist())->toBeTrue();
});

it('checklist collection supports collection methods', function () {
    $card1 = new Card(['id' => '1', 'name' => 'Card 1']);
    $card2 = new Card(['id' => '2', 'name' => 'Card 2']);
    $card3 = new Card(['id' => '3', 'name' => 'Card 3']);

    $set = new Set(['id' => '123']);
    $set->setRelationships(['checklist' => [$card1, $card2, $card3]]);

    $checklist = $set->checklist();

    // Test pluck
    $names = $checklist->pluck('name');
    expect($names->toArray())->toBe(['Card 1', 'Card 2', 'Card 3']);

    // Test filter
    $filteredCards = $checklist->filter(fn ($card) => $card->id === '2');
    expect($filteredCards)->toHaveCount(1);
    expect($filteredCards->first()->name)->toBe('Card 2');

    // Test first
    expect($checklist->first()->name)->toBe('Card 1');
});

it('returns current card count from checklist', function () {
    $card1 = new Card(['id' => '1', 'name' => 'Card 1']);
    $card2 = new Card(['id' => '2', 'name' => 'Card 2']);
    $card3 = new Card(['id' => '3', 'name' => 'Card 3']);
    $checklist = [$card1, $card2, $card3];

    $set = new Set(['id' => '123']);
    $set->setRelationships(['checklist' => $checklist]);

    expect($set->current_card_count)->toBe(3);
});

it('returns zero count for empty checklist', function () {
    $set = new Set(['id' => '123']);

    expect($set->current_card_count)->toBe(0);
});

it('returns previous card in checklist', function () {
    $card1 = new Card(['id' => '1', 'name' => 'Card 1']);
    $card2 = new Card(['id' => '2', 'name' => 'Card 2']);
    $card3 = new Card(['id' => '3', 'name' => 'Card 3']);
    $checklist = [$card1, $card2, $card3];

    $set = new Set(['id' => '123']);
    $set->setRelationships(['checklist' => $checklist]);

    // Test previous card for card2 (should return card1)
    $previousCard = $set->previousCard($card2);
    expect($previousCard)->toBe($card1);

    // Test previous card for card3 (should return card2)
    // No need to create new set instance since we're no longer using stateful index
    $previousCard2 = $set->previousCard($card3);
    expect($previousCard2)->toBe($card2);
});

it('returns null for previous card at beginning of checklist', function () {
    $card1 = new Card(['id' => '1', 'name' => 'Card 1']);
    $card2 = new Card(['id' => '2', 'name' => 'Card 2']);
    $checklist = [$card1, $card2];

    $set = new Set(['id' => '123']);
    $set->setRelationships(['checklist' => $checklist]);

    expect($set->previousCard($card1))->toBeNull();
});

it('returns null for previous card not in checklist', function () {
    $card1 = new Card(['id' => '1', 'name' => 'Card 1']);
    $card2 = new Card(['id' => '2', 'name' => 'Card 2']);
    $cardNotInList = new Card(['id' => '999', 'name' => 'Not in list']);

    $set = new Set(['id' => '123']);
    $set->setRelationships(['checklist' => [$card1, $card2]]);

    expect($set->previousCard($cardNotInList))->toBeNull();
});

it('returns next card in checklist', function () {
    $card1 = new Card(['id' => '1', 'name' => 'Card 1']);
    $card2 = new Card(['id' => '2', 'name' => 'Card 2']);
    $card3 = new Card(['id' => '3', 'name' => 'Card 3']);
    $checklist = [$card1, $card2, $card3];

    $set = new Set(['id' => '123']);
    $set->setRelationships(['checklist' => $checklist]);

    // Test next card for card1 (should return card2)
    $nextCard = $set->nextCard($card1);
    expect($nextCard)->toBe($card2);

    // Test next card for card2 (should return card3)
    // No need to create new set instance since we're no longer using stateful index
    $nextCard2 = $set->nextCard($card2);
    expect($nextCard2)->toBe($card3);
});

it('returns null for next card at end of checklist', function () {
    $card1 = new Card(['id' => '1', 'name' => 'Card 1']);
    $card2 = new Card(['id' => '2', 'name' => 'Card 2']);
    $checklist = [$card1, $card2];

    $set = new Set(['id' => '123']);
    $set->setRelationships(['checklist' => $checklist]);

    expect($set->nextCard($card2))->toBeNull();
});

it('returns null for next card not in checklist', function () {
    $card1 = new Card(['id' => '1', 'name' => 'Card 1']);
    $card2 = new Card(['id' => '2', 'name' => 'Card 2']);
    $cardNotInList = new Card(['id' => '999', 'name' => 'Not in list']);

    $set = new Set(['id' => '123']);
    $set->setRelationships(['checklist' => [$card1, $card2]]);

    expect($set->nextCard($cardNotInList))->toBeNull();
});

it('sets genre relationship when genres provided', function () {
    $genre1 = new Genre(['id' => '1', 'name' => 'Genre 1']);
    $genre2 = new Genre(['id' => '2', 'name' => 'Genre 2']);
    $genres = [$genre1, $genre2];

    $set = new Set(['id' => '123', 'genre_id' => '1']);
    $set->setRelationships(['genres' => $genres]);

    expect($set->genre())->toBe($genre1);
    expect($set->getRelationship('genres'))->toBeNull();
});

it('returns collection of sources', function () {
    $checklistSource = new \CardTechie\TradingCardApiSdk\Models\SetSource([
        'id' => '1',
        'source_type' => 'checklist',
        'source_name' => 'Beckett',
    ]);

    $metadataSource = new \CardTechie\TradingCardApiSdk\Models\SetSource([
        'id' => '2',
        'source_type' => 'metadata',
        'source_name' => 'COMC',
    ]);

    $sources = [$checklistSource, $metadataSource];

    $set = new Set(['id' => '123']);
    $set->setRelationships(['set-sources' => $sources]);

    $sourcesCollection = $set->sources();

    expect($sourcesCollection)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($sourcesCollection->count())->toBe(2);
    expect($sourcesCollection->first())->toBe($checklistSource);
});

it('returns empty collection when no sources', function () {
    $set = new Set(['id' => '123']);

    $sources = $set->sources();

    expect($sources)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($sources->isEmpty())->toBeTrue();
});

it('hasSources returns true when set has sources', function () {
    $source = new \CardTechie\TradingCardApiSdk\Models\SetSource([
        'id' => '1',
        'source_type' => 'checklist',
    ]);

    $set = new Set(['id' => '123']);
    $set->setRelationships(['set-sources' => [$source]]);

    expect($set->hasSources())->toBeTrue();
});

it('hasSources returns false when set has no sources', function () {
    $set = new Set(['id' => '123']);

    expect($set->hasSources())->toBeFalse();
});

it('getChecklistSource returns checklist source', function () {
    $checklistSource = new \CardTechie\TradingCardApiSdk\Models\SetSource([
        'id' => '1',
        'source_type' => 'checklist',
        'source_name' => 'Beckett',
    ]);

    $metadataSource = new \CardTechie\TradingCardApiSdk\Models\SetSource([
        'id' => '2',
        'source_type' => 'metadata',
        'source_name' => 'COMC',
    ]);

    $set = new Set(['id' => '123']);
    $set->setRelationships(['set-sources' => [$checklistSource, $metadataSource]]);

    expect($set->getChecklistSource())->toBe($checklistSource);
});

it('getMetadataSource returns metadata source', function () {
    $checklistSource = new \CardTechie\TradingCardApiSdk\Models\SetSource([
        'id' => '1',
        'source_type' => 'checklist',
        'source_name' => 'Beckett',
    ]);

    $metadataSource = new \CardTechie\TradingCardApiSdk\Models\SetSource([
        'id' => '2',
        'source_type' => 'metadata',
        'source_name' => 'COMC',
    ]);

    $set = new Set(['id' => '123']);
    $set->setRelationships(['set-sources' => [$metadataSource, $checklistSource]]);

    expect($set->getMetadataSource())->toBe($metadataSource);
});

it('getImagesSource returns images source', function () {
    $imagesSource = new \CardTechie\TradingCardApiSdk\Models\SetSource([
        'id' => '1',
        'source_type' => 'images',
        'source_name' => 'eBay',
    ]);

    $metadataSource = new \CardTechie\TradingCardApiSdk\Models\SetSource([
        'id' => '2',
        'source_type' => 'metadata',
        'source_name' => 'COMC',
    ]);

    $set = new Set(['id' => '123']);
    $set->setRelationships(['set-sources' => [$imagesSource, $metadataSource]]);

    expect($set->getImagesSource())->toBe($imagesSource);
});

it('source type helpers return null when source type does not exist', function () {
    $checklistSource = new \CardTechie\TradingCardApiSdk\Models\SetSource([
        'id' => '1',
        'source_type' => 'checklist',
        'source_name' => 'Beckett',
    ]);

    $set = new Set(['id' => '123']);
    $set->setRelationships(['set-sources' => [$checklistSource]]);

    expect($set->getMetadataSource())->toBeNull();
    expect($set->getImagesSource())->toBeNull();
});
