<?php

use CardTechie\TradingCardApiSdk\Models\CardImage as CardImageModel;
use CardTechie\TradingCardApiSdk\Resources\CardImage;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;

beforeEach(function () {
    // Set up configuration
    $this->app['config']->set('tradingcardapi', [
        'url' => 'https://api.example.com',
        'ssl_verify' => true,
        'client_id' => 'test-client-id',
        'client_secret' => 'test-client-secret',
    ]);

    // Pre-populate cache with token to avoid OAuth requests
    cache()->put('tcapi_token', 'test-token', 60);

    $this->mockHandler = new MockHandler;
    $handlerStack = HandlerStack::create($this->mockHandler);
    $this->client = new Client(['handler' => $handlerStack]);
    $this->cardImageResource = new CardImage($this->client);
});

it('can be instantiated with client', function () {
    expect($this->cardImageResource)->toBeInstanceOf(CardImage::class);
});

it('can get a card image by id', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'card-images',
                'id' => '123',
                'attributes' => [
                    'card_id' => '456',
                    'image_type' => 'front',
                    'storage_path' => '/uploads/image.jpg',
                    'file_size' => 1024000,
                    'mime_type' => 'image/jpeg',
                    'width' => 600,
                    'height' => 836,
                    'download_url' => 'https://cdn.example.com/image.jpg',
                ],
            ],
        ]))
    );

    $result = $this->cardImageResource->get('123');

    expect($result)->toBeInstanceOf(CardImageModel::class);
    expect($result->id)->toBe('123');
});

it('can get a card image with custom params', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'card-images',
                'id' => '123',
                'attributes' => [
                    'card_id' => '456',
                    'image_type' => 'front',
                    'file_size' => 1024000,
                    'mime_type' => 'image/jpeg',
                    'width' => 600,
                    'height' => 836,
                ],
            ],
        ]))
    );

    $params = ['include' => 'card'];
    $result = $this->cardImageResource->get('123', $params);

    expect($result)->toBeInstanceOf(CardImageModel::class);
});

it('can get a list of card images', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'card-images',
                    'id' => '123',
                    'attributes' => [
                        'card_id' => '456',
                        'image_type' => 'front',
                        'file_size' => 1024000,
                        'mime_type' => 'image/jpeg',
                        'width' => 600,
                        'height' => 836,
                    ],
                ],
            ],
            'meta' => [
                'pagination' => [
                    'total' => 100,
                    'per_page' => 50,
                    'current_page' => 1,
                ],
            ],
        ]))
    );

    $result = $this->cardImageResource->list();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

it('can get a list with custom params', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'card-images',
                    'id' => '123',
                    'attributes' => [
                        'card_id' => '456',
                        'image_type' => 'front',
                        'file_size' => 1024000,
                        'mime_type' => 'image/jpeg',
                        'width' => 600,
                        'height' => 836,
                    ],
                ],
            ],
            'meta' => [
                'pagination' => [
                    'total' => 100,
                    'per_page' => 25,
                    'current_page' => 2,
                ],
            ],
        ]))
    );

    $params = ['limit' => 25, 'page' => 2, 'filter' => ['card_id' => '456']];
    $result = $this->cardImageResource->list($params);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

it('can upload from file path', function () {
    // Create a temporary test file
    $testFilePath = sys_get_temp_dir().'/test-upload-'.uniqid().'.jpg';
    file_put_contents($testFilePath, 'fake image content');

    $this->mockHandler->append(
        new GuzzleResponse(201, [], json_encode([
            'data' => [
                'type' => 'card-images',
                'id' => '789',
                'attributes' => [
                    'card_id' => '456',
                    'image_type' => 'front',
                    'storage_path' => '/uploads/2024/01/image.jpg',
                    'file_size' => 1024000,
                    'mime_type' => 'image/jpeg',
                    'width' => 600,
                    'height' => 836,
                    'download_url' => 'https://cdn.example.com/image.jpg',
                ],
            ],
        ]))
    );

    $result = $this->cardImageResource->upload($testFilePath, '456', 'front');

    expect($result)->toBeInstanceOf(CardImageModel::class);
    expect($result->id)->toBe('789');
    expect($result->card_id)->toBe('456');

    // Clean up
    unlink($testFilePath);
});

it('can upload from UploadedFile', function () {
    // Create a temporary test file
    $testFilePath = sys_get_temp_dir().'/test-upload-'.uniqid().'.jpg';
    file_put_contents($testFilePath, 'fake image content');

    // Create UploadedFile instance
    $uploadedFile = new UploadedFile($testFilePath, 'card-front.jpg', 'image/jpeg', null, true);

    $this->mockHandler->append(
        new GuzzleResponse(201, [], json_encode([
            'data' => [
                'type' => 'card-images',
                'id' => '789',
                'attributes' => [
                    'card_id' => '456',
                    'image_type' => 'back',
                    'file_size' => 1024000,
                    'mime_type' => 'image/jpeg',
                    'width' => 600,
                    'height' => 836,
                ],
            ],
        ]))
    );

    $result = $this->cardImageResource->upload($uploadedFile, '456', 'back');

    expect($result)->toBeInstanceOf(CardImageModel::class);
    expect($result->image_type)->toBe('back');

    // Clean up
    unlink($testFilePath);
});

it('throws exception for invalid file input', function () {
    expect(fn () => $this->cardImageResource->upload('nonexistent-file.jpg', '456', 'front'))
        ->toThrow(\InvalidArgumentException::class);
});

it('can upload with additional attributes', function () {
    // Create a temporary test file
    $testFilePath = sys_get_temp_dir().'/test-upload-'.uniqid().'.jpg';
    file_put_contents($testFilePath, 'fake image content');

    $this->mockHandler->append(
        new GuzzleResponse(201, [], json_encode([
            'data' => [
                'type' => 'card-images',
                'id' => '789',
                'attributes' => [
                    'card_id' => '456',
                    'image_type' => 'front',
                    'storage_disk' => 's3',
                    'file_size' => 1024000,
                    'mime_type' => 'image/jpeg',
                    'width' => 600,
                    'height' => 836,
                ],
            ],
        ]))
    );

    $additionalAttrs = ['storage_disk' => 's3'];
    $result = $this->cardImageResource->upload($testFilePath, '456', 'front', $additionalAttrs);

    expect($result)->toBeInstanceOf(CardImageModel::class);

    // Clean up
    unlink($testFilePath);
});

it('can update card image metadata', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'card-images',
                'id' => '123',
                'attributes' => [
                    'card_id' => '456',
                    'image_type' => 'back',
                    'file_size' => 1024000,
                    'mime_type' => 'image/jpeg',
                    'width' => 600,
                    'height' => 836,
                ],
            ],
        ]))
    );

    $attributes = ['image_type' => 'back'];
    $result = $this->cardImageResource->update('123', $attributes);

    expect($result)->toBeInstanceOf(CardImageModel::class);
    expect($result->image_type)->toBe('back');
});

it('can delete a card image', function () {
    $this->mockHandler->append(
        new GuzzleResponse(204, [], '')
    );

    $this->cardImageResource->delete('123');

    expect(true)->toBeTrue();
});

it('can get download URL for original size', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'card-images',
                'id' => '123',
                'attributes' => [
                    'card_id' => '456',
                    'image_type' => 'front',
                    'download_url' => 'https://cdn.example.com/image.jpg',
                    'file_size' => 1024000,
                    'mime_type' => 'image/jpeg',
                    'width' => 600,
                    'height' => 836,
                ],
            ],
        ]))
    );

    $url = $this->cardImageResource->getDownloadUrl('123');

    expect($url)->toBe('https://cdn.example.com/image.jpg');
});

it('can get download URL for variant size', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'card-images',
                'id' => '123',
                'attributes' => [
                    'card_id' => '456',
                    'image_type' => 'front',
                    'download_url' => 'https://cdn.example.com/image.jpg',
                    'variants' => [
                        'small' => [
                            'url' => 'https://cdn.example.com/image-small.jpg',
                            'width' => 150,
                            'height' => 209,
                        ],
                    ],
                    'file_size' => 1024000,
                    'mime_type' => 'image/jpeg',
                    'width' => 600,
                    'height' => 836,
                ],
            ],
        ]))
    );

    $url = $this->cardImageResource->getDownloadUrl('123', 'small');

    expect($url)->toBe('https://cdn.example.com/image-small.jpg');
});

it('can get list with empty results', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [],
            'meta' => [
                'pagination' => [
                    'total' => 0,
                    'per_page' => 50,
                    'current_page' => 1,
                ],
            ],
        ]))
    );

    $result = $this->cardImageResource->list();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($result->total())->toBe(0);
});

it('can get card image with variants', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'card-images',
                'id' => '123',
                'attributes' => [
                    'card_id' => '456',
                    'image_type' => 'front',
                    'download_url' => 'https://cdn.example.com/image.jpg',
                    'variants' => [
                        'small' => [
                            'url' => 'https://cdn.example.com/image-small.jpg',
                            'width' => 150,
                            'height' => 209,
                            'file_size' => 12345,
                        ],
                        'medium' => [
                            'url' => 'https://cdn.example.com/image-medium.jpg',
                            'width' => 300,
                            'height' => 418,
                            'file_size' => 45678,
                        ],
                        'large' => [
                            'url' => 'https://cdn.example.com/image-large.jpg',
                            'width' => 600,
                            'height' => 836,
                            'file_size' => 123456,
                        ],
                    ],
                    'file_size' => 1024000,
                    'mime_type' => 'image/jpeg',
                    'width' => 800,
                    'height' => 1115,
                ],
            ],
        ]))
    );

    $result = $this->cardImageResource->get('123');

    expect($result)->toBeInstanceOf(CardImageModel::class);
    expect($result->variants)->toBeArray();
    expect($result->hasVariant('small'))->toBeTrue();
    expect($result->hasVariant('medium'))->toBeTrue();
    expect($result->hasVariant('large'))->toBeTrue();
});
