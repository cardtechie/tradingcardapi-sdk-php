<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Internal\Resources;

use CardTechie\TradingCardApiSdk\DTOs\Workflow\ActionableSetsResponse;
use CardTechie\TradingCardApiSdk\DTOs\Workflow\SetTodosResponse;
use CardTechie\TradingCardApiSdk\Enums\WorkflowStatus;
use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use GuzzleHttp\Client;
use Psr\SimpleCache\InvalidArgumentException;

class Workflow
{
    use ApiRequest;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get the actionable sets for the workflow dashboard.
     *
     * Returns a typed {@see ActionableSetsResponse} wrapping the collection of
     * actionable sets plus the API's `meta` block (total, full_total, and the
     * echoed filters).
     *
     * Targets the canonical `GET /internal/actionable-sets`. The former
     * `/internal/workflow/actionable-sets` path is a deprecated alias carrying
     * `deprecate.rfc8594` middleware.
     *
     * @param  array<string, mixed>  $params
     *
     * @throws InvalidArgumentException
     */
    public function actionableSets(array $params = []): ActionableSetsResponse
    {
        $url = '/internal/actionable-sets';
        if (! empty($params)) {
            $url .= '?'.http_build_query($params);
        }

        return ActionableSetsResponse::fromResponse($this->makeRequest($url, 'GET'));
    }

    /**
     * Update a workflow step (set-todo) status.
     *
     * Returns the raw decoded JSON:API acknowledgement (`data` resource
     * object); this mutation endpoint returns the updated todo envelope
     * rather than a typed DTO.
     *
     * Targets the canonical `PATCH /internal/sets/{set}/todos/{todo}`
     * (`internal.sets.todos.update`). There is no todo-id-only route, so the
     * set id is required.
     *
     * BREAKING (0.4.0): `$setId` was added as the first argument; the previous
     * two-argument form targeted `/internal/set-todos/{todo}`, a route the API
     * never registered. Actionable-set rows carry both `set_id` and `todo_id`,
     * so callers iterating {@see actionableSets()} have both ids available.
     *
     * @param  array<string, mixed>  $attributes
     * @return object The decoded JSON:API response (unstructured)
     *
     * @throws InvalidArgumentException
     */
    public function updateSetTodo(string $setId, string $todoId, array $attributes): object
    {
        $url = sprintf('/internal/sets/%s/todos/%s', $setId, $todoId);
        $request = [
            'json' => [
                'data' => [
                    'type' => 'set_todos',
                    'id' => $todoId,
                    'attributes' => $attributes,
                ],
            ],
        ];

        return $this->makeRequest($url, 'PATCH', $request);
    }

    /**
     * Bulk initialize workflow todos for existing sets.
     *
     * Returns the raw decoded job-acknowledgement object (`data.job_id`,
     * `data.status`); this endpoint queues an async job and returns an
     * unstructured ack rather than a typed DTO.
     *
     * Targets the canonical `POST /internal/todo-initialization-jobs`; the
     * former `/internal/workflow/bulk-initialize` path is a deprecated alias.
     *
     * @param  array<string, mixed>  $params
     * @return object The decoded job acknowledgement (unstructured)
     *
     * @throws InvalidArgumentException
     */
    public function bulkInitializeWorkflow(array $params = []): object
    {
        $request = ! empty($params) ? ['json' => $params] : [];

        return $this->makeRequest('/internal/todo-initialization-jobs', 'POST', $request);
    }

    /**
     * Check the status of a bulk initialization job.
     *
     * Returns the raw decoded job-status object (`data.job_id`,
     * `data.status`, and progress fields); this endpoint reports async job
     * progress as an unstructured ack rather than a typed DTO.
     *
     * Targets the canonical `GET /internal/todo-initialization-jobs/{job}`;
     * the former `/internal/workflow/bulk-initialize/{job}` path is a
     * deprecated alias.
     *
     * @return object The decoded job status (unstructured)
     *
     * @throws InvalidArgumentException
     */
    public function getBulkInitializeStatus(string $jobId): object
    {
        $url = sprintf('/internal/todo-initialization-jobs/%s', $jobId);

        return $this->makeRequest($url, 'GET');
    }

    /**
     * Get the workflow todos for a set.
     *
     * Returns a typed {@see SetTodosResponse} wrapping the per-set todo
     * collection.
     *
     * Targets the canonical `GET /internal/sets/{set}/todos`
     * (`internal.sets.todos.index`); the previous `/internal/workflow/sets/...`
     * path was never registered by the API and 404'd.
     *
     * @throws InvalidArgumentException
     */
    public function getSetTodos(string $setId): SetTodosResponse
    {
        $url = sprintf('/internal/sets/%s/todos', $setId);

        return SetTodosResponse::fromResponse($this->makeRequest($url, 'GET'));
    }

    /**
     * Get the workflow for a set.
     *
     * @throws InvalidArgumentException
     */
    public function getForSet(string $setId): object
    {
        $url = sprintf('/internal/sets/%s/workflow', $setId);

        return $this->makeRequest($url, 'GET');
    }

    /**
     * Get all sets currently blocked for human review,
     * optionally filtered by workflow step.
     *
     * Returns a typed {@see ActionableSetsResponse} (this delegates to
     * {@see actionableSets()} with a review-status filter applied).
     *
     * @param  string|null  $step  Optional workflow step to filter the review queue by.
     * @param  array<string, mixed>  $params
     *
     * @throws InvalidArgumentException
     */
    public function getReviewQueue(?string $step = null, array $params = []): ActionableSetsResponse
    {
        $params['status'] = WorkflowStatus::REVIEW->value;
        if ($step !== null) {
            $params['step'] = $step;
        }

        return $this->actionableSets($params);
    }

    /**
     * Flag a workflow step (set-todo) for human review.
     *
     * Delegates to {@see updateSetTodo()} and returns its raw decoded
     * JSON:API acknowledgement object.
     *
     * BREAKING (0.4.0): `$setId` was added as the first argument, mirroring
     * the {@see updateSetTodo()} signature change.
     *
     * @return object The decoded JSON:API response (unstructured)
     *
     * @throws InvalidArgumentException
     */
    public function flagForReview(string $setId, string $todoId, string $reason): object
    {
        return $this->updateSetTodo($setId, $todoId, [
            'status' => WorkflowStatus::REVIEW->value,
            'notes' => $reason,
        ]);
    }

    /**
     * Resolve a review by resetting a workflow step (set-todo) back to pending.
     *
     * Delegates to {@see updateSetTodo()} and returns its raw decoded
     * JSON:API acknowledgement object.
     *
     * BREAKING (0.4.0): `$setId` was added as the first argument, mirroring
     * the {@see updateSetTodo()} signature change.
     *
     * @return object The decoded JSON:API response (unstructured)
     *
     * @throws InvalidArgumentException
     */
    public function resolveReview(string $setId, string $todoId, string $notes = ''): object
    {
        return $this->updateSetTodo($setId, $todoId, [
            'status' => WorkflowStatus::PENDING->value,
            'notes' => $notes !== '' ? $notes : 'Resolved by human review',
        ]);
    }
}
