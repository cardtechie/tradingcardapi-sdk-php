<?php

use CardTechie\TradingCardApiSdk\Schemas\PlayerteamSchema;

it('has required validation rules for single playerteam', function () {
    $schema = new PlayerteamSchema();
    $rules = $schema->getRules();
    
    expect($rules)->toBeArray();
    expect($rules)->toHaveKey('data.id');
    expect($rules)->toHaveKey('data.type');
    expect($rules)->toHaveKey('data.attributes');
    
    // Check that type must be 'playerteams'
    expect($rules['data.type'])->toContain('in:playerteams');
});

it('has collection validation rules', function () {
    $schema = new PlayerteamSchema();
    $collectionRules = $schema->getCollectionRules();
    
    expect($collectionRules)->toBeArray();
    expect($collectionRules)->toHaveKey('data');
    expect($collectionRules)->toHaveKey('data.*');
    expect($collectionRules)->toHaveKey('data.*.id');
    expect($collectionRules)->toHaveKey('data.*.type');
    expect($collectionRules)->toHaveKey('data.*.attributes');
    
    // Check that each item's type must be 'playerteams'
    expect($collectionRules['data.*.type'])->toContain('in:playerteams');
});

it('validates playerteam-specific attributes', function () {
    $schema = new PlayerteamSchema();
    $rules = $schema->getRules();
    
    // Check for playerteam-specific attribute rules
    expect($rules)->toHaveKey('data.attributes.player_id');
    expect($rules)->toHaveKey('data.attributes.team_id');
});

it('allows optional playerteam attributes', function () {
    $schema = new PlayerteamSchema();
    $rules = $schema->getRules();
    
    // These should be optional (nullable) in the rules
    $optionalFields = [
        'data.attributes.start_date',
        'data.attributes.end_date',
        'data.attributes.jersey_number',
        'data.attributes.position',
    ];
    
    foreach ($optionalFields as $field) {
        if (isset($rules[$field])) {
            expect($rules[$field])->toContain('nullable');
        }
    }
});

it('validates collection with multiple playerteams', function () {
    $schema = new PlayerteamSchema();
    $collectionRules = $schema->getCollectionRules();
    
    // Check that collection rules properly handle multiple items
    expect($collectionRules['data'])->toContain('array');
    expect($collectionRules['data.*'])->toContain('array');
    expect($collectionRules['data.*.id'])->toContain('required');
    expect($collectionRules['data.*.type'])->toContain('required');
    expect($collectionRules['data.*.attributes'])->toContain('required');
});

it('includes meta validation for paginated collections', function () {
    $schema = new PlayerteamSchema();
    $collectionRules = $schema->getCollectionRules();
    
    // Should include meta validation for pagination
    expect($collectionRules)->toHaveKey('meta');
    expect($collectionRules['meta'])->toContain('sometimes');
    expect($collectionRules['meta'])->toContain('array');
});