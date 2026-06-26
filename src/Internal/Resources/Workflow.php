<?php

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
     * Returns a typed {@see ActionableSetsResponse} wrapping the JSON:API
     * collection of actionable sets.
     *
     * @param  array<string, mixed>  $params
     *
     * @throws InvalidArgumentException
     */
    public function actionableSets(array $params = []): ActionableSetsResponse
    {
        $url = '/internal/workflow/actionable-sets';
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
     * @param  array<string, mixed>  $attributes
     * @return object The decoded JSON:API response (unstructured)
     *
     * @throws InvalidArgumentException
     */
    public function updateSetTodo(string $todoId, array $attributes): object
    {
        $url = sprintf('/internal/set-todos/%s', $todoId);
        $request = [
            'json' => [
                'data' => [
                    'type' => 'set-todos',
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
     * @param  array<string, mixed>  $params
     * @return object The decoded job acknowledgement (unstructured)
     *
     * @throws InvalidArgumentException
     */
    public function bulkInitializeWorkflow(array $params = []): object
    {
        $request = ! empty($params) ? ['json' => $params] : [];

        return $this->makeRequest('/internal/workflow/bulk-initialize', 'POST', $request);
    }

    /**
     * Check the status of a bulk initialization job.
     *
     * Returns the raw decoded job-status object (`data.job_id`,
     * `data.status`, and progress fields); this endpoint reports async job
     * progress as an unstructured ack rather than a typed DTO.
     *
     * @return object The decoded job status (unstructured)
     *
     * @throws InvalidArgumentException
     */
    public function getBulkInitializeStatus(string $jobId): object
    {
        $url = sprintf('/internal/workflow/bulk-initialize/%s', $jobId);

        return $this->makeRequest($url, 'GET');
    }

    /**
     * Get the workflow todos for a set.
     *
     * Returns a typed {@see SetTodosResponse} wrapping the per-set todo
     * collection.
     *
     * @throws InvalidArgumentException
     */
    public function getSetTodos(string $setId): SetTodosResponse
    {
        $url = sprintf('/internal/workflow/sets/%s/todos', $setId);

        return SetTodosResponse::fromResponse($this->makeRequest($url, 'GET'));
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
     * @return object The decoded JSON:API response (unstructured)
     *
     * @throws InvalidArgumentException
     */
    public function flagForReview(string $todoId, string $reason): object
    {
        return $this->updateSetTodo($todoId, [
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
     * @return object The decoded JSON:API response (unstructured)
     *
     * @throws InvalidArgumentException
     */
    public function resolveReview(string $todoId, string $notes = ''): object
    {
        return $this->updateSetTodo($todoId, [
            'status' => WorkflowStatus::PENDING->value,
            'notes' => $notes !== '' ? $notes : 'Resolved by human review',
        ]);
    }
}
