<?php

use Carbon\Carbon;
use CardTechie\TradingCardApiSdk\Models\Set;
use CardTechie\TradingCardApiSdk\Models\SetSource;

it('can be instantiated with attributes', function () {
    $setSource = new SetSource([
        'id' => '123',
        'set_id' => '456',
        'source_type' => 'checklist',
        'source_name' => 'Beckett',
    ]);

    expect($setSource)->toBeInstanceOf(SetSource::class);
    expect($setSource->id)->toBe('123');
    expect($setSource->set_id)->toBe('456');
    expect($setSource->source_type)->toBe('checklist');
    expect($setSource->source_name)->toBe('Beckett');
});

it('returns set relationship', function () {
    $setSource = new SetSource(['id' => '123']);
    $set = new Set(['id' => '456', 'name' => '1989 Topps Baseball']);

    $setSource->setRelationships(['set' => [$set]]);

    expect($setSource->set())->toBe($set);
});

it('returns null when no set relationship', function () {
    $setSource = new SetSource(['id' => '123']);

    expect($setSource->set())->toBeNull();
});

it('parses verified_at as Carbon instance', function () {
    $setSource = new SetSource([
        'verified_at' => '2024-01-15T10:30:00Z',
    ]);

    expect($setSource->verified_at)->toBeInstanceOf(Carbon::class);
    expect($setSource->verified_at->format('Y-m-d'))->toBe('2024-01-15');
});

it('returns null for missing verified_at', function () {
    $setSource = new SetSource(['id' => '123']);

    expect($setSource->verified_at)->toBeNull();
});

it('parses created_at as Carbon instance', function () {
    $setSource = new SetSource([
        'created_at' => '2024-01-15T10:30:00Z',
    ]);

    expect($setSource->created_at)->toBeInstanceOf(Carbon::class);
    expect($setSource->created_at->format('Y-m-d'))->toBe('2024-01-15');
});

it('parses updated_at as Carbon instance', function () {
    $setSource = new SetSource([
        'updated_at' => '2024-01-15T15:45:00Z',
    ]);

    expect($setSource->updated_at)->toBeInstanceOf(Carbon::class);
    expect($setSource->updated_at->format('Y-m-d'))->toBe('2024-01-15');
});

it('returns null for missing created_at', function () {
    $setSource = new SetSource(['id' => '123']);

    expect($setSource->created_at)->toBeNull();
});

it('returns null for missing updated_at', function () {
    $setSource = new SetSource(['id' => '123']);

    expect($setSource->updated_at)->toBeNull();
});

it('handles all properties correctly', function () {
    $setSource = new SetSource([
        'id' => 'source-123',
        'set_id' => 'set-456',
        'source_type' => 'metadata',
        'source_name' => 'COMC',
        'source_url' => 'https://www.comc.com/...',
        'verified_at' => '2024-01-15T10:30:00Z',
        'created_at' => '2024-01-01T00:00:00Z',
        'updated_at' => '2024-01-15T10:30:00Z',
    ]);

    expect($setSource->id)->toBe('source-123');
    expect($setSource->set_id)->toBe('set-456');
    expect($setSource->source_type)->toBe('metadata');
    expect($setSource->source_name)->toBe('COMC');
    expect($setSource->source_url)->toBe('https://www.comc.com/...');
    expect($setSource->verified_at)->toBeInstanceOf(Carbon::class);
    expect($setSource->created_at)->toBeInstanceOf(Carbon::class);
    expect($setSource->updated_at)->toBeInstanceOf(Carbon::class);
});

it('handles checklist source_type', function () {
    $setSource = new SetSource([
        'source_type' => 'checklist',
        'source_name' => 'Beckett',
    ]);

    expect($setSource->source_type)->toBe('checklist');
});

it('handles metadata source_type', function () {
    $setSource = new SetSource([
        'source_type' => 'metadata',
        'source_name' => 'CardboardConnection',
    ]);

    expect($setSource->source_type)->toBe('metadata');
});

it('handles images source_type', function () {
    $setSource = new SetSource([
        'source_type' => 'images',
        'source_name' => 'eBay',
    ]);

    expect($setSource->source_type)->toBe('images');
});

it('handles nullable source_url', function () {
    $setSource = new SetSource([
        'source_name' => 'Physical Cards',
        'source_url' => null,
    ]);

    expect($setSource->source_url)->toBeNull();
});
