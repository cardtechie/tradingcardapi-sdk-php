<?php

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
            $this->getMetaLinksRules()
        );
    }

    /**
     * Get validation rules for Workflow collection responses
     */
    public function getCollectionRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiCollectionRules(),
            $this->getMetaLinksRules()
        );
    }
}
