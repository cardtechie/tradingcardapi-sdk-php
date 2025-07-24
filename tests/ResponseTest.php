<?php

use CardTechie\TradingCardApiSdk\Models\Card;
use CardTechie\TradingCardApiSdk\Models\Player;
use CardTechie\TradingCardApiSdk\Response;
use Illuminate\Support\Collection;

it('can parse single object response', function () {
    $json = json_encode([
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => [
                'name' => 'Test Card',
                'number' => '001',
            ],
        ],
    ]);

    $response = new Response($json);

    expect($response->mainObject)->toBeInstanceOf(Card::class);
    expect($response->mainObject->id)->toBe('123');
    expect($response->mainObject->name)->toBe('Test Card');
});

it('can parse response with included relationships', function () {
    $json = json_encode([
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => [
                'name' => 'Test Card',
            ],
        ],
        'included' => [
            [
                'id' => '456',
                'type' => 'players',
                'attributes' => [
                    'name' => 'Test Player',
                ],
            ],
        ],
    ]);

    $response = new Response($json);

    expect($response->relationships)->toHaveKey('players');
    expect($response->relationships['players'][0])->toBeInstanceOf(Player::class);
});

it('static parse method handles array of data', function () {
    $json = json_encode([
        'data' => [
            [
                'id' => '1',
                'type' => 'cards',
                'attributes' => ['name' => 'Card 1'],
            ],
            [
                'id' => '2',
                'type' => 'cards',
                'attributes' => ['name' => 'Card 2'],
            ],
        ],
    ]);

    $result = Response::parse($json);

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result)->toHaveCount(2);
    expect($result->first())->toBeInstanceOf(Card::class);
});

it('static parse method handles single object', function () {
    $json = json_encode([
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => ['name' => 'Test Card'],
        ],
    ]);

    $result = Response::parse($json);

    expect($result)->toBeInstanceOf(Card::class);
    expect($result->name)->toBe('Test Card');
});

it('handles meta data correctly', function () {
    $json = json_encode([
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => ['name' => 'Test Card'],
        ],
        'meta' => [
            'total' => 100,
            'per_page' => 10,
        ],
    ]);

    Response::parse($json);
    $meta = Response::getMeta();

    expect($meta->total)->toBe(100);
    expect($meta->per_page)->toBe(10);
});

it('handles links data correctly', function () {
    $json = json_encode([
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => ['name' => 'Test Card'],
        ],
        'links' => [
            'next' => 'https://api.example.com/cards?page=2',
            'prev' => null,
        ],
    ]);

    Response::parse($json);
    $links = Response::getLinks();

    expect($links->next)->toBe('https://api.example.com/cards?page=2');
    expect($links->prev)->toBeNull();
});

it('handles empty meta and links gracefully', function () {
    $json = json_encode([
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => ['name' => 'Test Card'],
        ],
    ]);

    Response::parse($json);
    $meta = Response::getMeta();
    $links = Response::getLinks();

    expect($meta)->toBeInstanceOf(stdClass::class);
    expect($links)->toBeInstanceOf(stdClass::class);
});

it('handles special type mappings in included', function () {
    $json = json_encode([
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => ['name' => 'Test Card'],
        ],
        'included' => [
            [
                'id' => '456',
                'type' => 'parentset',
                'attributes' => ['name' => 'Parent Set'],
            ],
            [
                'id' => '789',
                'type' => 'checklist',
                'attributes' => ['name' => 'Checklist Card'],
            ],
        ],
    ]);

    $response = new Response($json);

    expect($response->relationships)->toHaveKey('parentset');
    expect($response->relationships)->toHaveKey('checklist');
    expect($response->relationships['parentset'][0])->toBeInstanceOf(\CardTechie\TradingCardApiSdk\Models\Set::class);
    expect($response->relationships['checklist'][0])->toBeInstanceOf(Card::class);
});

it('handles response without included section', function () {
    $json = json_encode([
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => ['name' => 'Test Card'],
        ],
    ]);

    $response = new Response($json);

    expect($response->relationships)->toBe([]);
    expect($response->mainObject)->toBeInstanceOf(Card::class);
});
