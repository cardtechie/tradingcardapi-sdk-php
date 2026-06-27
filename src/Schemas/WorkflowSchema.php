<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Schemas;

/**
 * Schema for Workflow API responses (the /internal/workflow endpoint).
 *
 * This is the public-namespace resolution target the ResponseValidator looks
 * up (CardTechie\TradingCardApiSdk\Schemas\WorkflowSchema). The pre-existing
 * Internal\Schemas\WorkflowSchema is left untouched to avoid a breaking move.
 */
class WorkflowSchema extends BaseSchema
{
    /**
     * Get validation rules for a single Workflow response
     */
    public function getRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiRules(),
            $this->getMetaLinksRules(),
            $this->getWorkflowSpecificRules()
        );
    }

    /**
     * Get Workflow-specific validation rules
     *
     * Constrains data.type to the expected resource type(s), matching every
     * other JSON:API schema in this namespace (e.g. SetTodoSchema). Both the
     * plural and singular forms are accepted so the rule does not reject a
     * server that emits either spelling.
     */
    private function getWorkflowSpecificRules(): array
    {
        return [
            'data.type' => 'required|string|in:workflows,workflow',
        ];
    }

    /**
     * Get validation rules for Workflow collection responses
     */
    public function getCollectionRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiCollectionRules(),
            $this->getMetaLinksRules(),
            $this->getWorkflowCollectionSpecificRules()
        );
    }

    /**
     * Get Workflow collection-specific validation rules
     */
    private function getWorkflowCollectionSpecificRules(): array
    {
        return [
            'data.*.type' => 'required|string|in:workflows,workflow',
        ];
    }
}
