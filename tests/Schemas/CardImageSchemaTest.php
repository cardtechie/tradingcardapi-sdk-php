<?php

use CardTechie\TradingCardApiSdk\Schemas\CardImageSchema;
use Illuminate\Support\Facades\Validator;

it('provides validation rules for single card image response', function () {
    $schema = new CardImageSchema;
    $rules = $schema->getRules();

    // Should have base JSON API rules
    expect($rules)->toHaveKey('data');
    expect($rules)->toHaveKey('data.id');
    expect($rules)->toHaveKey('data.type');
    expect($rules)->toHaveKey('data.attributes');

    // Should have card image-specific rules
    expect($rules)->toHaveKey('data.attributes.card_id');
    expect($rules)->toHaveKey('data.attributes.image_type');
    expect($rules)->toHaveKey('data.attributes.storage_path');
    expect($rules)->toHaveKey('data.attributes.file_size');
    expect($rules)->toHaveKey('data.attributes.mime_type');
    expect($rules)->toHaveKey('data.attributes.width');
    expect($rules)->toHaveKey('data.attributes.height');

    // Should enforce card image type
    expect($rules['data.type'])->toContain('in:card-images,card_images,cardImages');
    expect($rules['data.attributes.image_type'])->toContain('in:front,back');
});

it('validates valid card image response successfully', function () {
    $schema = new CardImageSchema;

    $validCardImageData = [
        'data' => [
            'id' => '123',
            'type' => 'card-images',
            'attributes' => [
                'card_id' => 'card-456',
                'image_type' => 'front',
                'storage_path' => '/uploads/2024/01/image.jpg',
                'storage_disk' => 's3',
                'file_size' => 1024000,
                'mime_type' => 'image/jpeg',
                'width' => 600,
                'height' => 836,
                'download_url' => 'https://cdn.example.com/image.jpg',
                'created_at' => '2024-01-15T10:30:00Z',
                'updated_at' => '2024-01-15T10:30:00Z',
            ],
        ],
    ];

    $validator = Validator::make($validCardImageData, $schema->getRules());
    expect($validator->passes())->toBeTrue();
});

it('rejects invalid card image type', function () {
    $schema = new CardImageSchema;

    $invalidData = [
        'data' => [
            'id' => '123',
            'type' => 'cards', // Wrong type
            'attributes' => [
                'card_id' => 'card-456',
                'image_type' => 'front',
                'storage_path' => '/uploads/image.jpg',
                'file_size' => 1024000,
                'mime_type' => 'image/jpeg',
                'width' => 600,
                'height' => 836,
            ],
        ],
    ];

    $validator = Validator::make($invalidData, $schema->getRules());
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('data.type'))->toBeTrue();
});

it('rejects invalid image_type value', function () {
    $schema = new CardImageSchema;

    $invalidData = [
        'data' => [
            'id' => '123',
            'type' => 'card-images',
            'attributes' => [
                'card_id' => 'card-456',
                'image_type' => 'side', // Invalid value
                'storage_path' => '/uploads/image.jpg',
                'file_size' => 1024000,
                'mime_type' => 'image/jpeg',
                'width' => 600,
                'height' => 836,
            ],
        ],
    ];

    $validator = Validator::make($invalidData, $schema->getRules());
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('data.attributes.image_type'))->toBeTrue();
});

it('validates card image with variants', function () {
    $schema = new CardImageSchema;

    $dataWithVariants = [
        'data' => [
            'id' => '123',
            'type' => 'card-images',
            'attributes' => [
                'card_id' => 'card-456',
                'image_type' => 'back',
                'storage_path' => '/uploads/image.jpg',
                'file_size' => 1024000,
                'mime_type' => 'image/jpeg',
                'width' => 600,
                'height' => 836,
                'variants' => [
                    'small' => [
                        'url' => 'https://cdn.example.com/small.jpg',
                        'width' => 150,
                        'height' => 209,
                    ],
                    'medium' => [
                        'url' => 'https://cdn.example.com/medium.jpg',
                        'width' => 300,
                        'height' => 418,
                    ],
                ],
            ],
        ],
    ];

    $validator = Validator::make($dataWithVariants, $schema->getRules());
    expect($validator->passes())->toBeTrue();
});

it('validates card image with nullable optional fields', function () {
    $schema = new CardImageSchema;

    $dataWithNulls = [
        'data' => [
            'id' => '123',
            'type' => 'card-images',
            'attributes' => [
                'card_id' => 'card-456',
                'image_type' => 'front',
                'storage_path' => '/uploads/image.jpg',
                'file_size' => 1024000,
                'mime_type' => 'image/jpeg',
                'width' => 600,
                'height' => 836,
                'variants' => null,
                'storage_disk' => null,
                'download_url' => null,
            ],
        ],
    ];

    $validator = Validator::make($dataWithNulls, $schema->getRules());
    expect($validator->passes())->toBeTrue();
});

it('requires required fields', function () {
    $schema = new CardImageSchema;

    $missingRequiredFields = [
        'data' => [
            'id' => '123',
            'type' => 'card-images',
            'attributes' => [
                // Missing required fields
            ],
        ],
    ];

    $validator = Validator::make($missingRequiredFields, $schema->getRules());
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('data.attributes.card_id'))->toBeTrue();
    expect($validator->errors()->has('data.attributes.image_type'))->toBeTrue();
    expect($validator->errors()->has('data.attributes.storage_path'))->toBeTrue();
    expect($validator->errors()->has('data.attributes.file_size'))->toBeTrue();
    expect($validator->errors()->has('data.attributes.mime_type'))->toBeTrue();
    expect($validator->errors()->has('data.attributes.width'))->toBeTrue();
    expect($validator->errors()->has('data.attributes.height'))->toBeTrue();
});

it('provides validation rules for collection response', function () {
    $schema = new CardImageSchema;
    $rules = $schema->getCollectionRules();

    // Should have collection JSON API rules
    expect($rules)->toHaveKey('data');
    expect($rules)->toHaveKey('data.*.id');
    expect($rules)->toHaveKey('data.*.type');
    expect($rules)->toHaveKey('data.*.attributes');

    // Should have card image-specific rules for collection
    expect($rules)->toHaveKey('data.*.attributes.card_id');
    expect($rules)->toHaveKey('data.*.attributes.image_type');
    expect($rules)->toHaveKey('data.*.attributes.file_size');
});

it('validates valid card image collection response', function () {
    $schema = new CardImageSchema;

    $validCollectionData = [
        'data' => [
            [
                'id' => '123',
                'type' => 'card-images',
                'attributes' => [
                    'card_id' => 'card-456',
                    'image_type' => 'front',
                    'storage_path' => '/uploads/image1.jpg',
                    'file_size' => 1024000,
                    'mime_type' => 'image/jpeg',
                    'width' => 600,
                    'height' => 836,
                ],
            ],
            [
                'id' => '124',
                'type' => 'card-images',
                'attributes' => [
                    'card_id' => 'card-457',
                    'image_type' => 'back',
                    'storage_path' => '/uploads/image2.jpg',
                    'file_size' => 2048000,
                    'mime_type' => 'image/png',
                    'width' => 800,
                    'height' => 1115,
                ],
            ],
        ],
    ];

    $validator = Validator::make($validCollectionData, $schema->getCollectionRules());
    expect($validator->passes())->toBeTrue();
});

it('accepts alternative type formats', function () {
    $schema = new CardImageSchema;

    // Test card_images (snake_case)
    $data1 = [
        'data' => [
            'id' => '123',
            'type' => 'card_images',
            'attributes' => [
                'card_id' => 'card-456',
                'image_type' => 'front',
                'storage_path' => '/uploads/image.jpg',
                'file_size' => 1024000,
                'mime_type' => 'image/jpeg',
                'width' => 600,
                'height' => 836,
            ],
        ],
    ];

    $validator1 = Validator::make($data1, $schema->getRules());
    expect($validator1->passes())->toBeTrue();

    // Test cardImages (camelCase)
    $data2 = [
        'data' => [
            'id' => '123',
            'type' => 'cardImages',
            'attributes' => [
                'card_id' => 'card-456',
                'image_type' => 'front',
                'storage_path' => '/uploads/image.jpg',
                'file_size' => 1024000,
                'mime_type' => 'image/jpeg',
                'width' => 600,
                'height' => 836,
            ],
        ],
    ];

    $validator2 = Validator::make($data2, $schema->getRules());
    expect($validator2->passes())->toBeTrue();
});
