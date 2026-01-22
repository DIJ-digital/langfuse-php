<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Enums;

enum ScoreDataType: string
{
    case NUMERIC = 'NUMERIC';
    case CATEGORICAL = 'CATEGORICAL';
    case BOOLEAN = 'BOOLEAN';
}
