<?php

use Carbon\Carbon;
use CardTechie\TradingCardApiSdk\Models\Card;
use CardTechie\TradingCardApiSdk\Models\CardImage;

it('can be instantiated with attributes', function () {
    $cardImage = new CardImage([
        'id' => '123',
        'card_id' => '456',
        'image_type' => 'front',
        'file_size' => 1024000,
    ]);

    expect($cardImage)->toBeInstanceOf(CardImage::class);
    expect($cardImage->id)->toBe('123');
    expect($cardImage->card_id)->toBe('456');
    expect($cardImage->image_type)->toBe('front');
    expect($cardImage->file_size)->toBe(1024000);
});

it('returns card relationship', function () {
    $cardImage = new CardImage(['id' => '123']);
    $card = new Card(['id' => '456', 'name' => 'Test Card']);

    $cardImage->setRelationships(['card' => [$card]]);

    expect($cardImage->card())->toBe($card);
});

it('returns null when no card relationship', function () {
    $cardImage = new CardImage(['id' => '123']);

    expect($cardImage->card())->toBeNull();
});

it('returns CDN URL for original size', function () {
    $cardImage = new CardImage([
        'id' => '123',
        'download_url' => 'https://cdn.example.com/image.jpg',
    ]);

    expect($cardImage->getCdnUrl())->toBe('https://cdn.example.com/image.jpg');
    expect($cardImage->getCdnUrl('original'))->toBe('https://cdn.example.com/image.jpg');
});

it('returns CDN URL for variant size', function () {
    $cardImage = new CardImage([
        'id' => '123',
        'download_url' => 'https://cdn.example.com/image.jpg',
        'variants' => [
            'small' => [
                'url' => 'https://cdn.example.com/image-small.jpg',
                'width' => 150,
                'height' => 209,
            ],
            'medium' => [
                'url' => 'https://cdn.example.com/image-medium.jpg',
                'width' => 300,
                'height' => 418,
            ],
        ],
    ]);

    expect($cardImage->getCdnUrl('small'))->toBe('https://cdn.example.com/image-small.jpg');
    expect($cardImage->getCdnUrl('medium'))->toBe('https://cdn.example.com/image-medium.jpg');
});

it('returns null for non-existent variant', function () {
    $cardImage = new CardImage([
        'id' => '123',
        'download_url' => 'https://cdn.example.com/image.jpg',
    ]);

    expect($cardImage->getCdnUrl('large'))->toBeNull();
});

it('returns versioned URL with timestamp', function () {
    $updatedAt = '2024-01-15 10:30:00';
    $cardImage = new CardImage([
        'id' => '123',
        'download_url' => 'https://cdn.example.com/image.jpg',
        'updated_at' => $updatedAt,
    ]);

    $versionedUrl = $cardImage->getVersionedUrl();
    $timestamp = strtotime($updatedAt);

    expect($versionedUrl)->toContain('https://cdn.example.com/image.jpg');
    expect($versionedUrl)->toContain("v={$timestamp}");
});

it('returns versioned URL for variant', function () {
    $updatedAt = '2024-01-15 10:30:00';
    $cardImage = new CardImage([
        'id' => '123',
        'updated_at' => $updatedAt,
        'variants' => [
            'small' => [
                'url' => 'https://cdn.example.com/image-small.jpg',
                'width' => 150,
                'height' => 209,
            ],
        ],
    ]);

    $versionedUrl = $cardImage->getVersionedUrl('small');
    $timestamp = strtotime($updatedAt);

    expect($versionedUrl)->toContain('https://cdn.example.com/image-small.jpg');
    expect($versionedUrl)->toContain("v={$timestamp}");
});

it('checks if variant exists', function () {
    $cardImage = new CardImage([
        'variants' => [
            'small' => ['url' => 'https://cdn.example.com/small.jpg'],
            'medium' => ['url' => 'https://cdn.example.com/medium.jpg'],
        ],
    ]);

    expect($cardImage->hasVariant('small'))->toBeTrue();
    expect($cardImage->hasVariant('medium'))->toBeTrue();
    expect($cardImage->hasVariant('large'))->toBeFalse();
});

it('returns variant URL', function () {
    $cardImage = new CardImage([
        'variants' => [
            'small' => [
                'url' => 'https://cdn.example.com/small.jpg',
                'width' => 150,
            ],
        ],
    ]);

    expect($cardImage->getVariantUrl('small'))->toBe('https://cdn.example.com/small.jpg');
    expect($cardImage->getVariantUrl('large'))->toBeNull();
});

it('returns all variant sizes', function () {
    $cardImage = new CardImage([
        'variants' => [
            'small' => ['url' => 'https://cdn.example.com/small.jpg'],
            'medium' => ['url' => 'https://cdn.example.com/medium.jpg'],
            'large' => ['url' => 'https://cdn.example.com/large.jpg'],
        ],
    ]);

    $sizes = $cardImage->getVariantSizes();

    expect($sizes)->toBeArray();
    expect($sizes)->toContain('small');
    expect($sizes)->toContain('medium');
    expect($sizes)->toContain('large');
    expect(count($sizes))->toBe(3);
});

it('returns empty array when no variants', function () {
    $cardImage = new CardImage(['id' => '123']);

    expect($cardImage->getVariantSizes())->toBe([]);
});

it('returns download_url attribute', function () {
    $cardImage = new CardImage([
        'download_url' => 'https://cdn.example.com/image.jpg',
    ]);

    expect($cardImage->download_url)->toBe('https://cdn.example.com/image.jpg');
});

it('returns srcset for responsive images', function () {
    $cardImage = new CardImage([
        'download_url' => 'https://cdn.example.com/image.jpg',
        'width' => 600,
        'variants' => [
            'small' => [
                'url' => 'https://cdn.example.com/small.jpg',
                'width' => 150,
            ],
            'medium' => [
                'url' => 'https://cdn.example.com/medium.jpg',
                'width' => 300,
            ],
        ],
    ]);

    $srcset = $cardImage->srcset;

    expect($srcset)->toContain('https://cdn.example.com/small.jpg 150w');
    expect($srcset)->toContain('https://cdn.example.com/medium.jpg 300w');
    expect($srcset)->toContain('https://cdn.example.com/image.jpg 600w');
});

it('returns default sizes attribute', function () {
    $cardImage = new CardImage(['id' => '123']);

    expect($cardImage->sizes)->toBe('(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw');
});

it('parses created_at as Carbon instance', function () {
    $cardImage = new CardImage([
        'created_at' => '2024-01-15T10:30:00Z',
    ]);

    expect($cardImage->created_at)->toBeInstanceOf(Carbon::class);
    expect($cardImage->created_at->format('Y-m-d'))->toBe('2024-01-15');
});

it('parses updated_at as Carbon instance', function () {
    $cardImage = new CardImage([
        'updated_at' => '2024-01-15T15:45:00Z',
    ]);

    expect($cardImage->updated_at)->toBeInstanceOf(Carbon::class);
    expect($cardImage->updated_at->format('Y-m-d'))->toBe('2024-01-15');
});

it('returns null for missing created_at', function () {
    $cardImage = new CardImage(['id' => '123']);

    expect($cardImage->created_at)->toBeNull();
});

it('returns null for missing updated_at', function () {
    $cardImage = new CardImage(['id' => '123']);

    expect($cardImage->updated_at)->toBeNull();
});

it('handles all properties correctly', function () {
    $cardImage = new CardImage([
        'id' => 'img-123',
        'card_id' => 'card-456',
        'image_type' => 'back',
        'storage_path' => '/uploads/2024/01/image.jpg',
        'storage_disk' => 's3',
        'file_size' => 2048000,
        'mime_type' => 'image/jpeg',
        'width' => 800,
        'height' => 1115,
        'download_url' => 'https://cdn.example.com/image.jpg',
    ]);

    expect($cardImage->id)->toBe('img-123');
    expect($cardImage->card_id)->toBe('card-456');
    expect($cardImage->image_type)->toBe('back');
    expect($cardImage->storage_path)->toBe('/uploads/2024/01/image.jpg');
    expect($cardImage->storage_disk)->toBe('s3');
    expect($cardImage->file_size)->toBe(2048000);
    expect($cardImage->mime_type)->toBe('image/jpeg');
    expect($cardImage->width)->toBe(800);
    expect($cardImage->height)->toBe(1115);
    expect($cardImage->download_url)->toBe('https://cdn.example.com/image.jpg');
});
