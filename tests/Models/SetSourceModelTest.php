<?php

use CardTechie\TradingCardApiSdk\Models\Set;
use CardTechie\TradingCardApiSdk\Models\SetSource;

it('can be instantiated with attributes', function () {
    $setSource = new SetSource([
        'id' => '123',
        'set_id' => 'set-456',
        'source_url' => 'https://example.com/source',
        'source_name' => 'Example Source',
        'source_type' => 'checklist',
    ]);

    expect($setSource)->toBeInstanceOf(SetSource::class);
    expect($setSource->id)->toBe('123');
    expect($setSource->set_id)->toBe('set-456');
    expect($setSource->source_url)->toBe('https://example.com/source');
    expect($setSource->source_name)->toBe('Example Source');
    expect($setSource->source_type)->toBe('checklist');
});

it('can be instantiated with minimal attributes', function () {
    $setSource = new SetSource(['id' => '123']);

    expect($setSource)->toBeInstanceOf(SetSource::class);
    expect($setSource->id)->toBe('123');
});

it('handles null values for optional attributes', function () {
    $setSource = new SetSource([
        'id' => '123',
        'set_id' => 'set-456',
        'source_url' => 'https://example.com/source',
        'source_name' => null,
        'source_type' => 'metadata',
        'verified_at' => null,
    ]);

    expect($setSource->source_name)->toBeNull();
    expect($setSource->verified_at)->toBeNull();
});

it('handles timestamp attributes', function () {
    $setSource = new SetSource([
        'id' => '123',
        'verified_at' => '2024-01-15 10:30:00',
        'created_at' => '2024-01-01 00:00:00',
        'updated_at' => '2024-01-15 12:00:00',
    ]);

    expect($setSource->verified_at)->toBe('2024-01-15 10:30:00');
    expect($setSource->created_at)->toBe('2024-01-01 00:00:00');
    expect($setSource->updated_at)->toBe('2024-01-15 12:00:00');
});

it('handles different source types', function () {
    $checklistSource = new SetSource(['id' => '1', 'source_type' => 'checklist']);
    $metadataSource = new SetSource(['id' => '2', 'source_type' => 'metadata']);
    $imagesSource = new SetSource(['id' => '3', 'source_type' => 'images']);

    expect($checklistSource->source_type)->toBe('checklist');
    expect($metadataSource->source_type)->toBe('metadata');
    expect($imagesSource->source_type)->toBe('images');
});

it('returns set relationship', function () {
    $set = new Set(['id' => 'set-456', 'name' => 'Test Set']);
    $setSource = new SetSource(['id' => '123', 'set_id' => 'set-456']);
    $setSource->setRelationships(['set' => $set]);

    expect($setSource->set())->toBe($set);
    expect($setSource->set()->id)->toBe('set-456');
    expect($setSource->set()->name)->toBe('Test Set');
});

it('returns null when no set relationship', function () {
    $setSource = new SetSource(['id' => '123']);

    expect($setSource->set())->toBeNull();
});
