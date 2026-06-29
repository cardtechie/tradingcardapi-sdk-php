<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Schemas;

/**
 * Schema for AuditLog API responses
 */
class AuditLogSchema extends BaseSchema
{
    /**
     * Get validation rules for a single AuditLog response
     *
     * @return array<string, mixed>
     */
    public function getRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiRules(),
            $this->getMetaLinksRules(),
            $this->getAuditLogSpecificRules()
        );
    }

    /**
     * Get AuditLog-specific validation rules
     *
     * @return array<string, mixed>
     */
    private function getAuditLogSpecificRules(): array
    {
        return [
            'data.type' => 'required|string|in:audit-logs,audit-log',
            'data.attributes.auditable_type' => 'sometimes|string|nullable',
            'data.attributes.auditable_id' => 'sometimes|string|nullable',
            'data.attributes.event_type' => 'sometimes|string|nullable',
            'data.attributes.user_id' => 'sometimes|string|nullable',
            'data.attributes.ip_address' => 'sometimes|string|nullable',
            'data.attributes.old_values' => 'sometimes|string|nullable',
            'data.attributes.new_values' => 'sometimes|string|nullable',
            'data.attributes.created_at' => 'sometimes|string|nullable',
            'data.attributes.updated_at' => 'sometimes|string|nullable',
        ];
    }

    /**
     * Get validation rules for AuditLog collection responses
     *
     * @return array<string, mixed>
     */
    public function getCollectionRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiCollectionRules(),
            $this->getMetaLinksRules(),
            $this->getAuditLogCollectionSpecificRules()
        );
    }

    /**
     * Get AuditLog collection-specific validation rules
     *
     * @return array<string, mixed>
     */
    private function getAuditLogCollectionSpecificRules(): array
    {
        return [
            'data.*.type' => 'required|string|in:audit-logs,audit-log',
            'data.*.attributes.auditable_type' => 'sometimes|string|nullable',
            'data.*.attributes.auditable_id' => 'sometimes|string|nullable',
            'data.*.attributes.event_type' => 'sometimes|string|nullable',
            'data.*.attributes.user_id' => 'sometimes|string|nullable',
            'data.*.attributes.ip_address' => 'sometimes|string|nullable',
            'data.*.attributes.old_values' => 'sometimes|string|nullable',
            'data.*.attributes.new_values' => 'sometimes|string|nullable',
            'data.*.attributes.created_at' => 'sometimes|string|nullable',
            'data.*.attributes.updated_at' => 'sometimes|string|nullable',
        ];
    }
}
