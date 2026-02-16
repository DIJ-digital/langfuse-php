<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Enums;

enum SpanKind: string
{
    case TRACE = 'trace';
    case SPAN = 'span';
    case GENERATION = 'generation';
}
