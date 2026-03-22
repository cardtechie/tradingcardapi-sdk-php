<?php

namespace CardTechie\TradingCardApiSdk\Schemas;

/**
 * Schema for Workflow API responses
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
            $this->getMetaLinksRules()
        );
    }

    /**
     * Get validation rules for Workflow collection responses (e.g. actionable-sets)
     */
    public function getCollectionRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiCollectionRules(),
            $this->getMetaLinksRules()
        );
    }
}
