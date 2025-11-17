<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Resources;

use CardTechie\TradingCardApiSdk\Models\CardImage as CardImageModel;
use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use CardTechie\TradingCardApiSdk\Response;
use GuzzleHttp\Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class CardImage
 *
 * Handles card image operations including upload, retrieval, and management.
 */
class CardImage
{
    use ApiRequest;

    /**
     * CardImage constructor.
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Retrieve a list of card images
     *
     * @param  array  $params  Query parameters (filter, include, page, limit, etc.)
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function list(array $params = []): LengthAwarePaginator
    {
        $defaultParams = [
            'limit' => 50,
            'page' => 1,
            'pageName' => 'page',
        ];
        $params = array_merge($defaultParams, $params);

        $url = sprintf('/v1/card-images?%s', http_build_query($params));
        $response = $this->makeRequest($url);

        $totalPages = $response->meta->pagination->total;
        $perPage = $response->meta->pagination->per_page;
        $page = $response->meta->pagination->current_page;
        $options = [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => $params['pageName'],
        ];
        $parsedResponse = Response::parse(json_encode($response));

        return new LengthAwarePaginator($parsedResponse, $totalPages, $perPage, $page, $options);
    }

    /**
     * Retrieve a card image by ID
     *
     * @param  string  $id  Card image UUID
     * @param  array  $params  Query parameters (include, etc.)
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get(string $id, array $params = []): CardImageModel
    {
        $defaultParams = [
            'include' => 'card',
        ];
        $params = array_merge($defaultParams, $params);

        $url = sprintf('/v1/card-images/%s?%s', $id, http_build_query($params));
        $response = $this->makeRequest($url);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }

    /**
     * Upload a new card image
     *
     * @param  UploadedFile|string  $file  File to upload (UploadedFile or file path)
     * @param  string  $cardId  Card UUID
     * @param  string  $imageType  Image type (front|back)
     * @param  array  $attributes  Additional attributes
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \InvalidArgumentException
     */
    public function upload($file, string $cardId, string $imageType, array $attributes = []): CardImageModel
    {
        // Prepare the file for multipart upload
        if ($file instanceof UploadedFile) {
            $fileContents = fopen($file->getRealPath(), 'r');
            $filename = $file->getClientOriginalName();
        } elseif (is_string($file) && file_exists($file)) {
            $fileContents = fopen($file, 'r');
            $filename = basename($file);
        } else {
            throw new \InvalidArgumentException('File must be an UploadedFile instance or a valid file path');
        }

        // Prepare JSON:API data structure
        $data = [
            'type' => 'card-images',
            'attributes' => array_merge([
                'card_id' => $cardId,
                'image_type' => $imageType,
            ], $attributes),
        ];

        // Build multipart request
        $request = [
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => $fileContents,
                    'filename' => $filename,
                ],
                [
                    'name' => 'data',
                    'contents' => json_encode(['data' => $data]),
                    'headers' => ['Content-Type' => 'application/json'],
                ],
            ],
        ];

        $response = $this->makeRequest('/v1/card-images', 'POST', $request);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }

    /**
     * Update card image metadata
     *
     * @param  string  $id  Card image UUID
     * @param  array  $attributes  Attributes to update
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function update(string $id, array $attributes = []): CardImageModel
    {
        $url = sprintf('/v1/card-images/%s', $id);
        $request = [
            'json' => [
                'data' => [
                    'type' => 'card-images',
                    'id' => $id,
                    'attributes' => $attributes,
                ],
            ],
        ];

        $response = $this->makeRequest($url, 'PATCH', $request);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }

    /**
     * Delete a card image (soft delete)
     *
     * @param  string  $id  Card image UUID
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function delete(string $id): void
    {
        $url = '/v1/card-images/'.$id;
        $this->makeRequest($url, 'DELETE');
    }

    /**
     * Get the download URL for a card image
     *
     * @param  string  $id  Card image UUID
     * @param  string  $size  Image size (original, small, medium, large)
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getDownloadUrl(string $id, string $size = 'original'): string
    {
        // For the original size, use the standard download endpoint
        if ($size === 'original') {
            $url = sprintf('/v1/card-images/%s/download', $id);
        } else {
            // For variants, add size as query parameter
            $url = sprintf('/v1/card-images/%s/download?size=%s', $id, $size);
        }

        // Get the image metadata which includes the download URL
        $image = $this->get($id);

        if ($size === 'original') {
            return $image->download_url ?? '';
        }

        return $image->getVariantUrl($size) ?? '';
    }
}
