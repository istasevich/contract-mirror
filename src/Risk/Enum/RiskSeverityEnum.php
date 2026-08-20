<?php

declare(strict_types=1);

namespace App\Risk\Enum;

enum RiskSeverityEnum: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
}
