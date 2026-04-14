<?php

namespace CardTechie\TradingCardApiSdk\Enums;

enum WorkflowStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case SKIPPED = 'skipped';
    case REVIEW = 'review';
}
