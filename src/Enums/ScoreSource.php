<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Enums;

enum ScoreSource: string
{
    case API = 'API';
    case ANNOTATION = 'ANNOTATION';
    case EVAL = 'EVAL';
}
