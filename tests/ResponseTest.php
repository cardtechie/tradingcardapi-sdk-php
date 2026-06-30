<?php

declare(strict_types=1);

use CardTechie\TradingCardApiSdk\Models\AuditLog as AuditLogModel;
use CardTechie\TradingCardApiSdk\Models\Card;
use CardTechie\TradingCardApiSdk\Models\Player;
use CardTechie\TradingCardApiSdk\Models\Set;
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

it('attaches the set genre via JSON:API relationships linkage (constructor path)', function () {
    // After tradingcardapi-api#1491 the flat genre_id attribute is gone; the genre
    // is matched via data.relationships.genre.data.id against the included genres.
    $json = json_encode([
        'data' => [
            'id' => '123',
            'type' => 'sets',
            'attributes' => ['name' => 'Test Set'],
            'relationships' => [
                'genre' => [
                    'data' => ['type' => 'genres', 'id' => '2'],
                ],
            ],
        ],
        'included' => [
            ['id' => '1', 'type' => 'genres', 'attributes' => ['name' => 'Genre 1']],
            ['id' => '2', 'type' => 'genres', 'attributes' => ['name' => 'Genre 2']],
        ],
    ]);

    $response = new Response($json);
    $set = $response->mainObject;

    expect($set)->toBeInstanceOf(Set::class);
    expect($set->linkage)->toHaveKey('genre');
    expect($set->linkage['genre']['id'])->toBe('2');
    expect($set->genre())->not->toBeNull();
    expect($set->genre()->id)->toBe('2');
    expect($set->getRelationships())->not->toHaveKey('genres');
});

it('attaches the set genre via JSON:API relationships linkage (static parse path)', function () {
    $json = json_encode([
        'data' => [
            'id' => '123',
            'type' => 'sets',
            'attributes' => ['name' => 'Test Set'],
            'relationships' => [
                'genre' => [
                    'data' => ['type' => 'genres', 'id' => '1'],
                ],
            ],
        ],
        'included' => [
            ['id' => '1', 'type' => 'genres', 'attributes' => ['name' => 'Genre 1']],
            ['id' => '2', 'type' => 'genres', 'attributes' => ['name' => 'Genre 2']],
        ],
    ]);

    $set = Response::parse($json);

    expect($set)->toBeInstanceOf(Set::class);
    expect($set->genre())->not->toBeNull();
    expect($set->genre()->id)->toBe('1');
    expect($set->getRelationships())->not->toHaveKey('genres');
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

it('attaches top-level meta/links to the main object via the constructor path', function () {
    // Regression for #289: the non-static `new Response($json)` constructor path
    // never attached top-level meta/links to $this->mainObject, so single-object
    // responses parsed through the instance API silently dropped them. The main
    // object's getMeta()/getLinks() must now reflect the top-level meta/links,
    // matching the static parse() path.
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
        'links' => [
            'next' => 'https://api.example.com/cards?page=2',
            'prev' => null,
        ],
    ]);

    $response = new Response($json);

    expect($response->mainObject->getMeta()->total)->toBe(100);
    expect($response->mainObject->getMeta()->per_page)->toBe(10);
    expect($response->mainObject->getLinks()->next)->toBe('https://api.example.com/cards?page=2');
    expect($response->mainObject->getLinks()->prev)->toBeNull();
});

it('defaults main object meta/links to empty stdClass when absent (constructor path)', function () {
    // When the response carries no top-level meta/links, the constructor path
    // must leave the main object's meta/links as empty stdClass — the same
    // empty-object behavior the parseMeta()/parseLinks() helpers guarantee for
    // the static parse() path.
    $json = json_encode([
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => ['name' => 'Test Card'],
        ],
    ]);

    $response = new Response($json);

    expect($response->mainObject->getMeta())->toBeInstanceOf(stdClass::class);
    expect($response->mainObject->getLinks())->toBeInstanceOf(stdClass::class);
    expect((array) $response->mainObject->getMeta())->toBe([]);
    expect((array) $response->mainObject->getLinks())->toBe([]);
});

it('does not attach top-level meta/links to included models (constructor path)', function () {
    // Decision for #303: top-level JSON:API meta/links are document-scoped and
    // attach to the main parsed object only. Included relationship models must
    // return an empty stdClass from getMeta()/getLinks(), even when the response
    // carries top-level meta/links — copying document-scoped links onto an
    // included resource would falsely advertise links describing the main
    // collection.
    $json = json_encode([
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => ['name' => 'Test Card'],
        ],
        'included' => [
            [
                'id' => '456',
                'type' => 'players',
                'attributes' => ['name' => 'Test Player'],
            ],
        ],
        'meta' => ['total' => 100, 'per_page' => 10],
        'links' => ['next' => 'https://api.example.com/cards?page=2', 'prev' => null],
    ]);

    $response = new Response($json);

    // The main object reflects the top-level meta/links...
    expect($response->mainObject->getMeta()->total)->toBe(100);
    expect($response->mainObject->getLinks()->next)->toBe('https://api.example.com/cards?page=2');

    // ...while the included model carries neither.
    $includedPlayer = $response->relationships['players'][0];
    expect($includedPlayer->getMeta())->toBeInstanceOf(stdClass::class);
    expect($includedPlayer->getLinks())->toBeInstanceOf(stdClass::class);
    expect((array) $includedPlayer->getMeta())->toBe([]);
    expect((array) $includedPlayer->getLinks())->toBe([]);
});

it('does not attach top-level meta/links to included models (static parse path)', function () {
    // Same #303 contract, exercised through the static parse() entrypoint so the
    // two paths stay symmetric: included models get an empty stdClass for
    // meta/links while the main object carries the top-level document meta/links.
    $json = json_encode([
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => ['name' => 'Test Card'],
        ],
        'included' => [
            [
                'id' => '456',
                'type' => 'players',
                'attributes' => ['name' => 'Test Player'],
            ],
        ],
        'meta' => ['total' => 100, 'per_page' => 10],
        'links' => ['next' => 'https://api.example.com/cards?page=2', 'prev' => null],
    ]);

    $object = Response::parse($json);

    // The main parsed object reflects the top-level meta/links...
    expect($object->getMeta()->total)->toBe(100);
    expect($object->getLinks()->next)->toBe('https://api.example.com/cards?page=2');

    // ...while the included model carries neither.
    $includedPlayer = $object->getRelationships()['players'][0];
    expect($includedPlayer->getMeta())->toBeInstanceOf(stdClass::class);
    expect($includedPlayer->getLinks())->toBeInstanceOf(stdClass::class);
    expect((array) $includedPlayer->getMeta())->toBe([]);
    expect((array) $includedPlayer->getLinks())->toBe([]);
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

it('does not bleed meta/links across separate parses', function () {
    $jsonA = json_encode([
        'data' => [
            'id' => '1',
            'type' => 'cards',
            'attributes' => ['name' => 'Card A'],
        ],
        'meta' => ['total' => 100],
        'links' => ['next' => 'https://api.example.com/cards?page=2&doc=A'],
    ]);

    $jsonB = json_encode([
        'data' => [
            'id' => '2',
            'type' => 'cards',
            'attributes' => ['name' => 'Card B'],
        ],
        'meta' => ['total' => 7],
        'links' => ['next' => 'https://api.example.com/cards?page=2&doc=B'],
    ]);

    $resultA = Response::parse($jsonA);
    $resultB = Response::parse($jsonB);

    // Each parse result carries its own meta/links — the later parse of B must
    // not clobber the meta/links already captured on A's result.
    expect($resultA->getMeta()->total)->toBe(100);
    expect($resultA->getLinks()->next)->toBe('https://api.example.com/cards?page=2&doc=A');

    expect($resultB->getMeta()->total)->toBe(7);
    expect($resultB->getLinks()->next)->toBe('https://api.example.com/cards?page=2&doc=B');

    // And they are genuinely distinct, not aliased to the same shared object.
    // Compare the meta/links objects themselves (strict identity) so the test
    // fails if both results were ever to reference the same object instance —
    // a scalar-only comparison would not catch aliasing.
    expect($resultA->getMeta())->not->toBe($resultB->getMeta());
    expect($resultA->getLinks())->not->toBe($resultB->getLinks());
});

it('attaches per-result meta/links to every element of a collection parse', function () {
    $json = json_encode([
        'data' => [
            ['id' => '1', 'type' => 'cards', 'attributes' => ['name' => 'Card 1']],
            ['id' => '2', 'type' => 'cards', 'attributes' => ['name' => 'Card 2']],
        ],
        'meta' => ['total' => 2],
        'links' => ['next' => 'https://api.example.com/cards?page=2'],
    ]);

    $result = Response::parse($json);

    $result->each(function ($model) {
        expect($model->getMeta()->total)->toBe(2);
        expect($model->getLinks()->next)->toBe('https://api.example.com/cards?page=2');
    });
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
    expect($response->relationships['parentset'][0])->toBeInstanceOf(Set::class);
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

it('resolves audit-logs type to AuditLog model', function () {
    $json = json_encode([
        'data' => [
            'id' => '42',
            'type' => 'audit-logs',
            'attributes' => [
                'event_type' => 'created',
                'auditable_type' => 'Set',
                'auditable_id' => 'set-123',
            ],
        ],
    ]);

    $response = new Response($json);

    expect($response->mainObject)->toBeInstanceOf(AuditLogModel::class);
    expect($response->mainObject->id)->toBe('42');
    expect($response->mainObject->event_type)->toBe('created');
});

it('resolves audit-logs type via static parse method', function () {
    $json = json_encode([
        'data' => [
            [
                'id' => '1',
                'type' => 'audit-logs',
                'attributes' => ['event_type' => 'created'],
            ],
            [
                'id' => '2',
                'type' => 'audit-logs',
                'attributes' => ['event_type' => 'updated'],
            ],
        ],
    ]);

    $result = Response::parse($json);

    expect($result)->toHaveCount(2);
    expect($result->first())->toBeInstanceOf(AuditLogModel::class);
    expect($result->first()->event_type)->toBe('created');
    expect($result->last())->toBeInstanceOf(AuditLogModel::class);
    expect($result->last()->event_type)->toBe('updated');
});
