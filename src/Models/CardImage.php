<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Models;

use Carbon\Carbon;

/**
 * Class CardImage
 *
 * Represents a card image in the Trading Card API.
 *
 * @property string $id Card image UUID
 * @property string $card_id Related card UUID
 * @property string $image_type Image type (front|back)
 * @property string $storage_path Storage path for the image
 * @property array $variants Thumbnail variant metadata
 * @property string $storage_disk Storage disk identifier
 * @property int $file_size File size in bytes
 * @property string $mime_type MIME type of the image
 * @property int $width Image width in pixels
 * @property int $height Image height in pixels
 * @property string $download_url CDN download URL (computed)
 * @property string $srcset Responsive image srcset attribute (computed)
 * @property string $sizes Responsive image sizes attribute (computed)
 * @property Carbon $created_at Creation timestamp
 * @property Carbon $updated_at Last update timestamp
 */
class CardImage extends Model
{
    /**
     * Get the related card.
     */
    public function card(): ?Card
    {
        return $this->getRelationship('card');
    }

    /**
     * Get the CDN URL for a specific size variant.
     *
     * @param  string|null  $size  Variant size (small, medium, large) or null for original
     */
    public function getCdnUrl(?string $size = null): ?string
    {
        if ($size === null || $size === 'original') {
            return $this->attributes['download_url'] ?? null;
        }

        return $this->getVariantUrl($size);
    }

    /**
     * Get a versioned URL for cache busting.
     *
     * @param  string|null  $size  Variant size (small, medium, large) or null for original
     */
    public function getVersionedUrl(?string $size = null): string
    {
        $url = $this->getCdnUrl($size);
        if (! $url) {
            return '';
        }

        $timestamp = isset($this->attributes['updated_at'])
            ? strtotime($this->attributes['updated_at'])
            : time();

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.'v='.$timestamp;
    }

    /**
     * Get the URL for a specific variant size.
     */
    public function getVariantUrl(string $size): ?string
    {
        if (! $this->hasVariant($size)) {
            return null;
        }

        $variants = $this->getVariantsAsArray();

        return $variants[$size]['url'] ?? null;
    }

    /**
     * Check if a variant size exists.
     */
    public function hasVariant(string $size): bool
    {
        $variants = $this->getVariantsAsArray();

        return isset($variants[$size]);
    }

    /**
     * Get all available variant sizes.
     *
     * @return array<string> Array of variant size names
     */
    public function getVariantSizes(): array
    {
        $variants = $this->getVariantsAsArray();

        return array_keys($variants);
    }

    /**
     * Get variants as array (handles object conversion).
     *
     * @return array<string, array>
     */
    private function getVariantsAsArray(): array
    {
        if (! isset($this->attributes['variants'])) {
            return [];
        }

        // Convert stdClass to array if needed
        if (is_object($this->attributes['variants'])) {
            return json_decode(json_encode($this->attributes['variants']), true);
        }

        return is_array($this->attributes['variants']) ? $this->attributes['variants'] : [];
    }

    /**
     * Get the variants attribute as array.
     *
     * @return array<string, array>|null
     */
    public function getVariantsAttribute(): ?array
    {
        if (! isset($this->attributes['variants'])) {
            return null;
        }

        return $this->getVariantsAsArray();
    }

    /**
     * Get the download_url attribute.
     */
    public function getDownloadUrlAttribute(): ?string
    {
        return $this->attributes['download_url'] ?? null;
    }

    /**
     * Get the srcset attribute for responsive images.
     */
    public function getSrcsetAttribute(): string
    {
        $srcset = [];
        $variants = $this->getVariantsAsArray();

        // Add variants
        foreach ($variants as $size => $variant) {
            if (isset($variant['url'], $variant['width'])) {
                $srcset[] = $variant['url'].' '.$variant['width'].'w';
            }
        }

        // Add original image
        if (isset($this->attributes['download_url'], $this->attributes['width'])) {
            $srcset[] = $this->attributes['download_url'].' '.$this->attributes['width'].'w';
        }

        return implode(', ', $srcset);
    }

    /**
     * Get the sizes attribute for responsive images.
     */
    public function getSizesAttribute(): string
    {
        // Default responsive sizes configuration
        return '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw';
    }

    /**
     * Get the created_at attribute as Carbon instance.
     */
    public function getCreatedAtAttribute(): ?Carbon
    {
        if (! isset($this->attributes['created_at'])) {
            return null;
        }

        return Carbon::parse($this->attributes['created_at']);
    }

    /**
     * Get the updated_at attribute as Carbon instance.
     */
    public function getUpdatedAtAttribute(): ?Carbon
    {
        if (! isset($this->attributes['updated_at'])) {
            return null;
        }

        return Carbon::parse($this->attributes['updated_at']);
    }
}
