<?php

declare(strict_types=1);

namespace DIJ\Langfuse\PHP\Enums;

enum DatasetStatus: string
{
    case ACTIVE = 'ACTIVE';
    case ARCHIVED = 'ARCHIVED';
}
