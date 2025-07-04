<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Enums;

enum EventType: string
{
    case TRACE_CREATE = 'trace-create';
    case SCORE_CREATE = 'score-create';
    case SPAN_CREATE = 'span-create';
    case SPAN_UPDATE = 'span-update';
    case GENERATION_CREATE = 'generation-create';
    case GENERATION_UPDATE = 'generation-update';
    case EVENT_CREATE = 'event-create';
    case SDK_LOG = 'sdk-log';
    case OBSERVATION_CREATE = 'observation-create';
}
