<?php

namespace CardTechie\TradingCardApiSdk\Resources;

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
        $url = '/v1/workflow/actionable-sets';
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
        $url = sprintf('/v1/set-todos/%s', $todoId);
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

        return $this->makeRequest('/v1/workflow/bulk-initialize', 'POST', $request);
    }

    /**
     * Check the status of a bulk initialization job.
     *
     * @throws InvalidArgumentException
     */
    public function getBulkInitializeStatus(string $jobId): object
    {
        $url = sprintf('/v1/workflow/bulk-initialize/%s', $jobId);

        return $this->makeRequest($url, 'GET');
    }

    /**
     * Get the workflow todos for a set.
     *
     * @throws InvalidArgumentException
     */
    public function getSetTodos(string $setId): object
    {
        $url = sprintf('/v1/workflow/sets/%s/todos', $setId);

        return $this->makeRequest($url, 'GET');
    }
}
