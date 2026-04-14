<?php

namespace CardTechie\TradingCardApiSdk\Resources;

use CardTechie\TradingCardApiSdk\Models\AuditLog as AuditLogModel;
use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use CardTechie\TradingCardApiSdk\Response;
use GuzzleHttp\Client;
use Illuminate\Pagination\LengthAwarePaginator;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class AuditLog
 */
class AuditLog
{
    use ApiRequest;

    /**
     * AuditLog constructor.
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Retrieve a paginated list of audit logs with optional filters
     *
     * @param  array<string, mixed>  $params  Filter parameters: auditable_type, auditable_id, event_type, start_date, end_date, per_page, page
     *
     * @throws InvalidArgumentException
     */
    public function getAuditLogs(array $params = []): LengthAwarePaginator
    {
        $defaultParams = [
            'per_page' => 50,
            'page' => 1,
            'pageName' => 'page',
        ];
        $params = array_merge($defaultParams, $params);

        $url = sprintf('/v1/audit-logs?%s', http_build_query($params));
        $response = $this->makeRequest($url);

        // Handle missing meta information gracefully
        $totalPages = isset($response->meta->pagination->total) ? $response->meta->pagination->total : count($response->data);
        $perPage = isset($response->meta->pagination->per_page) ? $response->meta->pagination->per_page : ($params['per_page'] ?? 50);
        $page = isset($response->meta->pagination->current_page) ? $response->meta->pagination->current_page : ($params['page'] ?? 1);
        $options = [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => $params['pageName'],
        ];
        $parsedResponse = Response::parse(json_encode($response));

        return new LengthAwarePaginator($parsedResponse, $totalPages, $perPage, $page, $options);
    }

    /**
     * Create a new audit event
     *
     * @param  array<string, mixed>  $attributes  Audit event attributes
     *
     * @throws InvalidArgumentException
     */
    public function createAuditEvent(array $attributes = []): AuditLogModel
    {
        $request = [
            'json' => [
                'data' => [
                    'type' => 'audit-logs',
                ],
            ],
        ];

        if (count($attributes)) {
            $request['json']['data']['attributes'] = $attributes;
        }

        $response = $this->makeRequest('/v1/audit-logs', 'POST', $request);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }
}
