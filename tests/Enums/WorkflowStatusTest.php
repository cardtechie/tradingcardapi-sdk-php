<?php

use CardTechie\TradingCardApiSdk\Enums\WorkflowStatus;

it('has the correct status values', function () {
    expect(WorkflowStatus::PENDING->value)->toBe('pending');
    expect(WorkflowStatus::IN_PROGRESS->value)->toBe('in_progress');
    expect(WorkflowStatus::COMPLETED->value)->toBe('completed');
    expect(WorkflowStatus::SKIPPED->value)->toBe('skipped');
    expect(WorkflowStatus::REVIEW->value)->toBe('review');
});

it('has exactly five cases', function () {
    expect(WorkflowStatus::cases())->toHaveCount(5);
});

it('can be created from a valid string value', function () {
    expect(WorkflowStatus::from('pending'))->toBe(WorkflowStatus::PENDING);
    expect(WorkflowStatus::from('in_progress'))->toBe(WorkflowStatus::IN_PROGRESS);
    expect(WorkflowStatus::from('completed'))->toBe(WorkflowStatus::COMPLETED);
    expect(WorkflowStatus::from('skipped'))->toBe(WorkflowStatus::SKIPPED);
    expect(WorkflowStatus::from('review'))->toBe(WorkflowStatus::REVIEW);
});

it('returns null for tryFrom with an invalid value', function () {
    expect(WorkflowStatus::tryFrom('invalid'))->toBeNull();
});
