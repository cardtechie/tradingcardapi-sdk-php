<?php

declare(strict_types=1);

use CardTechie\TradingCardApiSdk\Models\Attribute;
use CardTechie\TradingCardApiSdk\Models\AuditLog;
use CardTechie\TradingCardApiSdk\Models\Brand;
use CardTechie\TradingCardApiSdk\Models\Card;
use CardTechie\TradingCardApiSdk\Models\CardImage;
use CardTechie\TradingCardApiSdk\Models\Genre;
use CardTechie\TradingCardApiSdk\Models\Manufacturer;
use CardTechie\TradingCardApiSdk\Models\Model;
use CardTechie\TradingCardApiSdk\Models\ObjectAttribute;
use CardTechie\TradingCardApiSdk\Models\Oncard;
use CardTechie\TradingCardApiSdk\Models\Player;
use CardTechie\TradingCardApiSdk\Models\Playerteam;
use CardTechie\TradingCardApiSdk\Models\Set;
use CardTechie\TradingCardApiSdk\Models\SetSource;
use CardTechie\TradingCardApiSdk\Models\Taxonomy;
use CardTechie\TradingCardApiSdk\Models\Team;
use CardTechie\TradingCardApiSdk\Models\Year;
use Mockery\MockInterface;

/**
 * Regression coverage for issue #351.
 *
 * `Models\Model::__call()` used to declare a native `never` return type.
 * Mockery's generated override for such a method completes normally, which PHP
 * treats as a hard fatal ("A never-returning method must not return") rather
 * than a catchable error — so `Mockery::mock()` aborted the whole test process
 * for every SDK model. These tests fail loudly if the native type is re-added.
 */
afterEach(function () {
    Mockery::close();
});

it('never declares a native never return type on Model::__call()', function () {
    $returnType = (new ReflectionMethod(Model::class, '__call'))->getReturnType();

    expect($returnType)->toBeNull();
});

it('can be mocked by Mockery', function (string $class) {
    $mock = Mockery::mock($class);

    expect($mock)->toBeInstanceOf(MockInterface::class);
})->with([
    Attribute::class,
    AuditLog::class,
    Brand::class,
    Card::class,
    CardImage::class,
    Genre::class,
    Manufacturer::class,
    Model::class,
    ObjectAttribute::class,
    Oncard::class,
    Player::class,
    Playerteam::class,
    Set::class,
    SetSource::class,
    Taxonomy::class,
    Team::class,
    Year::class,
]);

it('still throws BadMethodCallException on a real instance after the type change', function () {
    $model = new Model(['id' => '123']);

    expect(fn () => $model->nonExistentMethod())
        ->toThrow(BadMethodCallException::class, 'Call to undefined method '.Model::class.'::nonExistentMethod()');
});
