<?php

use CardTechie\TradingCardApiSdk\Enums\WorkflowStep;

it('has the correct step values', function () {
    expect(WorkflowStep::DISCOVER_SOURCES->value)->toBe('discover_sources');
    expect(WorkflowStep::FETCH->value)->toBe('fetch');
    expect(WorkflowStep::PARSE->value)->toBe('parse');
    expect(WorkflowStep::POPULATE->value)->toBe('populate');
    expect(WorkflowStep::VALIDATE->value)->toBe('validate');
    expect(WorkflowStep::CLEANUP->value)->toBe('cleanup');
    expect(WorkflowStep::PUBLISH->value)->toBe('publish');
});

it('has exactly seven cases', function () {
    expect(WorkflowStep::cases())->toHaveCount(7);
});

it('can be created from a valid string value', function () {
    expect(WorkflowStep::from('discover_sources'))->toBe(WorkflowStep::DISCOVER_SOURCES);
    expect(WorkflowStep::from('fetch'))->toBe(WorkflowStep::FETCH);
    expect(WorkflowStep::from('parse'))->toBe(WorkflowStep::PARSE);
    expect(WorkflowStep::from('populate'))->toBe(WorkflowStep::POPULATE);
    expect(WorkflowStep::from('validate'))->toBe(WorkflowStep::VALIDATE);
    expect(WorkflowStep::from('cleanup'))->toBe(WorkflowStep::CLEANUP);
    expect(WorkflowStep::from('publish'))->toBe(WorkflowStep::PUBLISH);
});

it('returns null for tryFrom with an invalid value', function () {
    expect(WorkflowStep::tryFrom('invalid'))->toBeNull();
});
