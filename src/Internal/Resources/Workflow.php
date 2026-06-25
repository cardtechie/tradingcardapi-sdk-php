<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Internal\Resources;

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
     * @param  array<string, mixed>  $params
     *
     * @throws InvalidArgumentException
     */
    public function actionableSets(array $params = []): object
    {
        $url = '/internal/workflow/actionable-sets';
        if (! empty($params)) {
            $url .= '?'.http_build_query($params);
        }

        return $this->makeRequest($url, 'GET');
    }

    /**
     * Update a workflow step (set-todo) status.
     *
     * @param  array<string, mixed>  $attributes
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
     * @param  array<string, mixed>  $params
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
     * @throws InvalidArgumentException
     */
    public function getSetTodos(string $setId): object
    {
        $url = sprintf('/internal/workflow/sets/%s/todos', $setId);

        return $this->makeRequest($url, 'GET');
    }

    /**
     * Get all sets currently blocked for human review,
     * optionally filtered by workflow step.
     *
     * @param  array<string, mixed>  $params
     *
     * @throws InvalidArgumentException
     */
    public function getReviewQueue(?string $step = null, array $params = []): object
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
