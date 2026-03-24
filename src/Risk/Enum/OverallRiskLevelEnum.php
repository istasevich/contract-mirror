<?php declare(strict_types=1);

namespace App\Risk\Enum;

enum OverallRiskLevelEnum: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
}
