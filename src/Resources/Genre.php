<?php

namespace CardTechie\TradingCardApiSdk\Resources;

use CardTechie\TradingCardApiSdk\Models\Genre as GenreModel;
use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use CardTechie\TradingCardApiSdk\Response;
use GuzzleHttp\Client;
use Illuminate\Pagination\LengthAwarePaginator;

class Genre
{
    use ApiRequest;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function create(array $attributes = [], array $relationships = []): GenreModel
    {
        $request = [
            'json' => [
                'data' => [
                    'type' => 'genres',
                ],
            ],
        ];

        if (count($attributes)) {
            $request['json']['data']['attributes'] = $attributes;
        }

        if (count($relationships)) {
            $request['json']['data']['relationships'] = $relationships;
        }

        $response = $this->makeRequest('/v1/genres', 'POST', $request);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }

    public function get(string $id, array $params = []): GenreModel
    {
        $url = sprintf('/v1/genres/%s', $id);
        $response = $this->makeRequest($url, 'GET', ['query' => $params]);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }

    public function list(array $params = []): LengthAwarePaginator
    {
        $defaultParams = [
            'limit' => 50,
            'page' => 1,
            'pageName' => 'page',
        ];
        $params = array_merge($defaultParams, $params);

        $url = sprintf('/v1/genres?%s', http_build_query($params));
        $response = $this->makeRequest($url);

        // Handle missing meta information gracefully
        $totalPages = isset($response->meta->pagination->total) ? $response->meta->pagination->total : count($response->data);
        $perPage = isset($response->meta->pagination->per_page) ? $response->meta->pagination->per_page : ($params['limit'] ?? 50);
        $page = isset($response->meta->pagination->current_page) ? $response->meta->pagination->current_page : ($params['page'] ?? 1);
        $options = [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => $params['pageName'],
        ];
        $parsedResponse = Response::parse(json_encode($response));

        return new LengthAwarePaginator($parsedResponse, $totalPages, $perPage, $page, $options);
    }

    public function update(string $id, array $attributes = [], array $relationships = []): GenreModel
    {
        $url = sprintf('/v1/genres/%s', $id);
        $request = [
            'json' => [
                'data' => [
                    'type' => 'genres',
                    'id' => $id,
                ],
            ],
        ];

        if (count($attributes)) {
            $request['json']['data']['attributes'] = $attributes;
        }

        if (count($relationships)) {
            $request['json']['data']['relationships'] = $relationships;
        }

        $response = $this->makeRequest($url, 'PUT', $request);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }

    public function delete(string $id): void
    {
        $url = sprintf('/v1/genres/%s', $id);
        $this->makeRequest($url, 'DELETE');
    }

    public function listDeleted(): LengthAwarePaginator
    {
        $response = $this->makeRequest('/v1/genres/deleted');

        $totalPages = $response->meta->pagination->total ?? count($response->data);
        $perPage = $response->meta->pagination->per_page ?? max(count($response->data), 1);
        $page = $response->meta->pagination->current_page ?? 1;
        $options = [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => 'page',
        ];
        $parsedResponse = Response::parse(json_encode($response));

        return new LengthAwarePaginator($parsedResponse, $totalPages, $perPage, $page, $options);
    }

    public function deleted(string $id): GenreModel
    {
        $url = sprintf('/v1/genres/%s/deleted', $id);
        $response = $this->makeRequest($url);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }
}
