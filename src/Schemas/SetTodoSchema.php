<?php

namespace CardTechie\TradingCardApiSdk\Schemas;

/**
 * Schema for SetTodo API responses (the /internal/set-todos endpoint)
 */
class SetTodoSchema extends BaseSchema
{
    /**
     * Get validation rules for a single SetTodo response
     */
    public function getRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiRules(),
            $this->getMetaLinksRules(),
            $this->getSetTodoSpecificRules()
        );
    }

    /**
     * Get SetTodo-specific validation rules
     *
     * Status/step values originate from the WorkflowStatus/WorkflowStep enums,
     * but the rules are kept as string|nullable rather than a hard in: list so
     * validation does not reject forward-compatible API values.
     */
    private function getSetTodoSpecificRules(): array
    {
        return [
            'data.type' => 'required|string|in:set-todos,set-todo',
            'data.attributes.status' => 'sometimes|string|nullable',
            'data.attributes.step' => 'sometimes|string|nullable',
            'data.attributes.set_id' => 'sometimes|string|nullable',
            'data.attributes.notes' => 'sometimes|string|nullable',
            'data.attributes.created_at' => 'sometimes|string|nullable',
            'data.attributes.updated_at' => 'sometimes|string|nullable',
        ];
    }

    /**
     * Get validation rules for SetTodo collection responses
     */
    public function getCollectionRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiCollectionRules(),
            $this->getMetaLinksRules(),
            $this->getSetTodoCollectionSpecificRules()
        );
    }

    /**
     * Get SetTodo collection-specific validation rules
     */
    private function getSetTodoCollectionSpecificRules(): array
    {
        return [
            'data.*.type' => 'required|string|in:set-todos,set-todo',
            'data.*.attributes.status' => 'sometimes|string|nullable',
            'data.*.attributes.step' => 'sometimes|string|nullable',
            'data.*.attributes.set_id' => 'sometimes|string|nullable',
            'data.*.attributes.notes' => 'sometimes|string|nullable',
            'data.*.attributes.created_at' => 'sometimes|string|nullable',
            'data.*.attributes.updated_at' => 'sometimes|string|nullable',
        ];
    }
}
