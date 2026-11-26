<?php

declare(strict_types=1);

use CardTechie\TradingCardApiSdk\DTOs\Workflow\ActionableSet;

it('preserves a flat API row as attributes instead of discarding it', function () {
    $row = (object) [
        'todo_id' => 'todo-1',
        'set_id' => 'set-1',
        'set_name' => '2024 Topps Baseball',
        'genre' => 'baseball',
        'year' => 2024,
        'step' => 'discover_sources',
        'priority' => 'high',
        'card_count' => 350,
        'has_sources' => false,
        'notes' => null,
        'updated_at' => '2026-03-15T09:00:00+00:00',
    ];

    $set = ActionableSet::fromObject($row);

    expect($set->id)->toBe('set-1');
    expect($set->type)->toBe('sets');
    expect($set->todoId)->toBe('todo-1');
    expect($set->attributes->set_name)->toBe('2024 Topps Baseball');
    expect($set->attributes->step)->toBe('discover_sources');
    expect($set->attributes->has_sources)->toBeFalse();
    expect($set->attributes->notes)->toBeNull();
});

it('still parses a JSON:API resource object', function () {
    $item = (object) [
        'id' => '1',
        'type' => 'sets',
        'attributes' => (object) ['name' => '2024 Topps Baseball', 'status' => 'draft'],
    ];

    $set = ActionableSet::fromObject($item);

    expect($set->id)->toBe('1');
    expect($set->type)->toBe('sets');
    expect($set->attributes->name)->toBe('2024 Topps Baseball');
    expect($set->todoId)->toBeNull();
});

it('reads the todo id out of JSON:API attributes when present', function () {
    $item = (object) [
        'id' => '1',
        'type' => 'sets',
        'attributes' => (object) ['todo_id' => 'todo-9'],
    ];

    expect(ActionableSet::fromObject($item)->todoId)->toBe('todo-9');
});

it('defaults id to an empty string when the flat row carries no set id', function () {
    $set = ActionableSet::fromObject((object) ['set_name' => 'Orphan']);

    expect($set->id)->toBe('');
    expect($set->type)->toBe('sets');
    expect($set->todoId)->toBeNull();
    expect($set->attributes->set_name)->toBe('Orphan');
});

it('handles a completely empty row', function () {
    $set = ActionableSet::fromObject((object) []);

    expect($set->id)->toBe('');
    expect($set->type)->toBe('sets');
    expect($set->attributes)->toBeObject();
    expect($set->todoId)->toBeNull();
});

it('casts a numeric set id and todo id to strings', function () {
    $set = ActionableSet::fromObject((object) ['set_id' => 7, 'todo_id' => 12]);

    expect($set->id)->toBe('7');
    expect($set->todoId)->toBe('12');
});
