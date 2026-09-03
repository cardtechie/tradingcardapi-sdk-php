<?php

declare(strict_types=1);

use CardTechie\TradingCardApiSdk\Models\Model;

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

    expect($mock)->toBeInstanceOf(Mockery\MockInterface::class);
})->with([
    CardTechie\TradingCardApiSdk\Models\Attribute::class,
    CardTechie\TradingCardApiSdk\Models\AuditLog::class,
    CardTechie\TradingCardApiSdk\Models\Brand::class,
    CardTechie\TradingCardApiSdk\Models\Card::class,
    CardTechie\TradingCardApiSdk\Models\CardImage::class,
    CardTechie\TradingCardApiSdk\Models\Genre::class,
    CardTechie\TradingCardApiSdk\Models\Manufacturer::class,
    CardTechie\TradingCardApiSdk\Models\Model::class,
    CardTechie\TradingCardApiSdk\Models\ObjectAttribute::class,
    CardTechie\TradingCardApiSdk\Models\Oncard::class,
    CardTechie\TradingCardApiSdk\Models\Player::class,
    CardTechie\TradingCardApiSdk\Models\Playerteam::class,
    CardTechie\TradingCardApiSdk\Models\Set::class,
    CardTechie\TradingCardApiSdk\Models\SetSource::class,
    CardTechie\TradingCardApiSdk\Models\Taxonomy::class,
    CardTechie\TradingCardApiSdk\Models\Team::class,
    CardTechie\TradingCardApiSdk\Models\Year::class,
]);

it('still throws BadMethodCallException on a real instance after the type change', function () {
    $model = new Model(['id' => '123']);

    expect(fn () => $model->nonExistentMethod())
        ->toThrow(BadMethodCallException::class, 'Call to undefined method '.Model::class.'::nonExistentMethod()');
});
