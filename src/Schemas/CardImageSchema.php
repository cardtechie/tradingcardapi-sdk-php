<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Schemas;

/**
 * Schema for CardImage API responses
 */
class CardImageSchema extends BaseSchema
{
    /**
     * Get validation rules for CardImage responses
     */
    public function getRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiRules(),
            $this->getMetaLinksRules(),
            $this->getCardImageSpecificRules()
        );
    }

    /**
     * Get CardImage-specific validation rules
     */
    private function getCardImageSpecificRules(): array
    {
        return [
            'data.type' => 'required|string|in:card-images,card_images,cardImages',
            'data.attributes.card_id' => 'required|string',
            'data.attributes.image_type' => 'required|string|in:front,back',
            'data.attributes.storage_path' => 'required|string',
            'data.attributes.variants' => 'sometimes|array|nullable',
            'data.attributes.storage_disk' => 'sometimes|string|nullable',
            'data.attributes.file_size' => 'required|integer',
            'data.attributes.mime_type' => 'required|string',
            'data.attributes.width' => 'required|integer',
            'data.attributes.height' => 'required|integer',
            'data.attributes.download_url' => 'sometimes|string|nullable',
            'data.attributes.created_at' => 'sometimes|string|nullable',
            'data.attributes.updated_at' => 'sometimes|string|nullable',
        ];
    }

    /**
     * Get validation rules for CardImage collection responses
     */
    public function getCollectionRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiCollectionRules(),
            $this->getMetaLinksRules(),
            $this->getCardImageCollectionSpecificRules()
        );
    }

    /**
     * Get CardImage collection-specific validation rules
     */
    private function getCardImageCollectionSpecificRules(): array
    {
        return [
            'data.*.type' => 'required|string|in:card-images,card_images,cardImages',
            'data.*.attributes.card_id' => 'required|string',
            'data.*.attributes.image_type' => 'required|string|in:front,back',
            'data.*.attributes.storage_path' => 'required|string',
            'data.*.attributes.variants' => 'sometimes|array|nullable',
            'data.*.attributes.storage_disk' => 'sometimes|string|nullable',
            'data.*.attributes.file_size' => 'required|integer',
            'data.*.attributes.mime_type' => 'required|string',
            'data.*.attributes.width' => 'required|integer',
            'data.*.attributes.height' => 'required|integer',
            'data.*.attributes.download_url' => 'sometimes|string|nullable',
            'data.*.attributes.created_at' => 'sometimes|string|nullable',
            'data.*.attributes.updated_at' => 'sometimes|string|nullable',
        ];
    }
}
