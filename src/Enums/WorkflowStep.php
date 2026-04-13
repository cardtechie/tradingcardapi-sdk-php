<?php

namespace CardTechie\TradingCardApiSdk\Enums;

enum WorkflowStep: string
{
    case DISCOVER_SOURCES = 'discover_sources';
    case FETCH = 'fetch';
    case PARSE = 'parse';
    case POPULATE = 'populate';
    case VALIDATE = 'validate';
    case CLEANUP = 'cleanup';
    case PUBLISH = 'publish';
}
