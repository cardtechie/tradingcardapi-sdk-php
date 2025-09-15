<?php

use CardTechie\TradingCardApiSdk\Exceptions\SetNotFoundException;

it('creates set not found exception by ID', function () {
    $exception = SetNotFoundException::byId('set789', ['year' => '1995']);

    expect($exception->getMessage())->toBe("The set with ID 'set789' was not found");
    expect($exception->getCode())->toBe(404);
    expect($exception->getHttpStatusCode())->toBe(404);
    expect($exception->getApiErrorCode())->toBe('set_not_found');
    expect($exception->getApiErrors())->toHaveCount(1);
    expect($exception->getApiErrors()[0]['title'])->toBe('Set Not Found');
    expect($exception->getContext())->toHaveKey('resource_type', 'set');
    expect($exception->getContext())->toHaveKey('resource_id', 'set789');
    expect($exception->getContext())->toHaveKey('year', '1995');
});

it('creates set not found exception by name', function () {
    $exception = SetNotFoundException::byName('1989 Upper Deck', ['series' => 'baseball']);

    expect($exception->getMessage())->toBe("Set '1989 Upper Deck' was not found");
    expect($exception->getApiErrorCode())->toBe('set_not_found');
    expect($exception->getApiErrors())->toHaveCount(1);
    expect($exception->getApiErrors()[0]['title'])->toBe('Set Not Found');
    expect($exception->getApiErrors()[0]['detail'])->toBe("No set found with the name '1989 Upper Deck'");
    expect($exception->getContext())->toHaveKey('set_name', '1989 Upper Deck');
    expect($exception->getContext())->toHaveKey('series', 'baseball');
});

it('inherits from ResourceNotFoundException', function () {
    $exception = SetNotFoundException::byId('test123');
    expect($exception)->toBeInstanceOf(\CardTechie\TradingCardApiSdk\Exceptions\ResourceNotFoundException::class);
});