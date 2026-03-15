<?php

use CardTechie\TradingCardApiSdk\Models\Card;
use CardTechie\TradingCardApiSdk\Models\Set;

it('can be instantiated with attributes', function () {
    $card = new Card(['id' => '123', 'name' => 'Test Card']);

    expect($card)->toBeInstanceOf(Card::class);
    expect($card->id)->toBe('123');
    expect($card->name)->toBe('Test Card');
});

it('formats number attribute correctly with set prefix', function () {
    $card = new Card(['number' => 'PREFIX001']);

    $set = new Set(['number_prefix' => 'PREFIX']);
    $card->setRelationships(['set' => [$set]]);

    expect($card->number)->toBe('001');
});

it('returns full number when no set prefix', function () {
    $card = new Card(['number' => '001']);

    expect($card->number)->toBe('001');
});

it('returns full number attribute', function () {
    $card = new Card(['number' => 'PREFIX001']);

    expect($card->full_number)->toBe('PREFIX001');
});

it('returns oncard relationships collection', function () {
    $card = new Card(['id' => '123']);

    // Create mock oncard objects that extend Model
    $oncard1 = new class(['on_cardable_type' => 'players', 'on_cardable_id' => '1']) extends \CardTechie\TradingCardApiSdk\Models\Model
    {
        public $on_cardable_type = 'players';

        public $on_cardable_id = '1';
    };

    $oncard2 = new class(['on_cardable_type' => 'players', 'on_cardable_id' => '2']) extends \CardTechie\TradingCardApiSdk\Models\Model
    {
        public $on_cardable_type = 'players';

        public $on_cardable_id = '2';
    };

    $oncard = [$oncard1, $oncard2];

    $card->setRelationships(['oncard' => $oncard]);

    $oncardCollection = $card->oncard();

    expect($oncardCollection)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($oncardCollection)->toHaveCount(2);
    expect($oncardCollection->get(0))->toBe($oncard1);
    expect($oncardCollection->get(1))->toBe($oncard2);
});

it('returns empty collection when no oncard relationships', function () {
    $card = new Card(['id' => '123']);

    $oncard = $card->oncard();

    expect($oncard)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($oncard)->toBeEmpty();
});

it('hasOncard returns false when no oncard relationships', function () {
    $card = new Card(['id' => '123']);

    expect($card->hasOncard())->toBeFalse();
});

it('hasOncard returns true when oncard relationships exist', function () {
    $card = new Card(['id' => '123']);

    $oncard1 = new class(['on_cardable_type' => 'players', 'on_cardable_id' => '1']) extends \CardTechie\TradingCardApiSdk\Models\Model
    {
        public $on_cardable_type = 'players';

        public $on_cardable_id = '1';
    };

    $card->setRelationships(['oncard' => [$oncard1]]);

    expect($card->hasOncard())->toBeTrue();
});

it('oncard collection supports collection methods', function () {
    $card = new Card(['id' => '123']);

    $oncard1 = new class(['on_cardable_type' => 'players', 'on_cardable_id' => '1']) extends \CardTechie\TradingCardApiSdk\Models\Model
    {
        public $on_cardable_type = 'players';

        public $on_cardable_id = '1';
    };

    $oncard2 = new class(['on_cardable_type' => 'teams', 'on_cardable_id' => '2']) extends \CardTechie\TradingCardApiSdk\Models\Model
    {
        public $on_cardable_type = 'teams';

        public $on_cardable_id = '2';
    };

    $card->setRelationships(['oncard' => [$oncard1, $oncard2]]);

    $oncard = $card->oncard();

    // Test filter
    $players = $oncard->filter(fn ($item) => $item->on_cardable_type === 'players');
    expect($players)->toHaveCount(1);

    // Test first
    expect($oncard->first())->toBe($oncard1);

    // Test pluck
    $types = $oncard->pluck('on_cardable_type');
    expect($types->toArray())->toBe(['players', 'teams']);
});

it('returns extra attributes collection', function () {
    $card = new Card(['id' => '123']);
    $attributes = ['special' => 'value'];

    $card->setRelationships(['attributes' => $attributes]);

    $extraAttrs = $card->extraAttributes();

    expect($extraAttrs)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($extraAttrs->toArray())->toBe($attributes);
});

it('returns empty collection when no extra attributes', function () {
    $card = new Card(['id' => '123']);

    $extraAttrs = $card->extraAttributes();

    expect($extraAttrs)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($extraAttrs)->toBeEmpty();
});

it('hasExtraAttributes returns false when no extra attributes', function () {
    $card = new Card(['id' => '123']);

    expect($card->hasExtraAttributes())->toBeFalse();
});

it('hasExtraAttributes returns true when extra attributes exist', function () {
    $card = new Card(['id' => '123']);
    $attributes = ['special' => 'value'];

    $card->setRelationships(['attributes' => $attributes]);

    expect($card->hasExtraAttributes())->toBeTrue();
});

it('returns set relationship', function () {
    $card = new Card(['id' => '123']);
    $set = new Set(['id' => '456', 'name' => 'Test Set']);

    $card->setRelationships(['set' => [$set]]);

    expect($card->set())->toBe($set);
});

it('returns null when no set relationship', function () {
    $card = new Card(['id' => '123']);

    expect($card->set())->toBeNull();
});

it('handles complex relationships in setRelationships', function () {
    $card = new Card(['id' => '123']);

    $playerteam = new \CardTechie\TradingCardApiSdk\Models\Playerteam([
        'id' => 'pt1',
        'player_id' => 'p1',
        'team_id' => 't1',
    ]);

    $player = new \CardTechie\TradingCardApiSdk\Models\Player(['id' => 'p1', 'name' => 'Player 1']);
    $team = new \CardTechie\TradingCardApiSdk\Models\Team(['id' => 't1', 'name' => 'Team 1']);

    $relationships = [
        'playerteam' => [$playerteam],
        'player' => [$player],
        'team' => [$team],
    ];

    $card->setRelationships($relationships);

    expect($card->getRelationships())->toHaveKey('playerteam');
    expect($card->getRelationships()['playerteam'][0])->toBe($playerteam);
});

it('returns collection of images', function () {
    $card = new Card(['id' => '123']);

    $frontImage = new \CardTechie\TradingCardApiSdk\Models\CardImage([
        'id' => 'img1',
        'image_type' => 'front',
        'download_url' => 'https://example.com/front.jpg',
    ]);

    $backImage = new \CardTechie\TradingCardApiSdk\Models\CardImage([
        'id' => 'img2',
        'image_type' => 'back',
        'download_url' => 'https://example.com/back.jpg',
    ]);

    $card->setRelationships(['card-images' => [$frontImage, $backImage]]);

    $images = $card->images();

    expect($images)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($images->count())->toBe(2);
    expect($images->first())->toBe($frontImage);
});

it('returns empty collection when no images', function () {
    $card = new Card(['id' => '123']);

    $images = $card->images();

    expect($images)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($images->isEmpty())->toBeTrue();
});

it('hasImages returns true when card has images', function () {
    $card = new Card(['id' => '123']);

    $image = new \CardTechie\TradingCardApiSdk\Models\CardImage([
        'id' => 'img1',
        'image_type' => 'front',
    ]);

    $card->setRelationships(['card-images' => [$image]]);

    expect($card->hasImages())->toBeTrue();
});

it('hasImages returns false when card has no images', function () {
    $card = new Card(['id' => '123']);

    expect($card->hasImages())->toBeFalse();
});

it('getFrontImage returns front image', function () {
    $card = new Card(['id' => '123']);

    $frontImage = new \CardTechie\TradingCardApiSdk\Models\CardImage([
        'id' => 'img1',
        'image_type' => 'front',
        'download_url' => 'https://example.com/front.jpg',
    ]);

    $backImage = new \CardTechie\TradingCardApiSdk\Models\CardImage([
        'id' => 'img2',
        'image_type' => 'back',
        'download_url' => 'https://example.com/back.jpg',
    ]);

    $card->setRelationships(['card-images' => [$frontImage, $backImage]]);

    expect($card->getFrontImage())->toBe($frontImage);
});

it('getBackImage returns back image', function () {
    $card = new Card(['id' => '123']);

    $frontImage = new \CardTechie\TradingCardApiSdk\Models\CardImage([
        'id' => 'img1',
        'image_type' => 'front',
        'download_url' => 'https://example.com/front.jpg',
    ]);

    $backImage = new \CardTechie\TradingCardApiSdk\Models\CardImage([
        'id' => 'img2',
        'image_type' => 'back',
        'download_url' => 'https://example.com/back.jpg',
    ]);

    $card->setRelationships(['card-images' => [$backImage, $frontImage]]);

    expect($card->getBackImage())->toBe($backImage);
});

it('getFrontImage returns null when no front image exists', function () {
    $card = new Card(['id' => '123']);

    $backImage = new \CardTechie\TradingCardApiSdk\Models\CardImage([
        'id' => 'img1',
        'image_type' => 'back',
    ]);

    $card->setRelationships(['card-images' => [$backImage]]);

    expect($card->getFrontImage())->toBeNull();
});

it('getBackImage returns null when no back image exists', function () {
    $card = new Card(['id' => '123']);

    $frontImage = new \CardTechie\TradingCardApiSdk\Models\CardImage([
        'id' => 'img1',
        'image_type' => 'front',
    ]);

    $card->setRelationships(['card-images' => [$frontImage]]);

    expect($card->getBackImage())->toBeNull();
});
