<?php

declare(strict_types=1);

namespace App\Risk\Service;

use App\Risk\Enum\OverallRiskLevelEnum;

final class RiskScoreCalculator
{
    /**
     * @param array<int, array<string, mixed>> $issues
     * @return array{riskScore:int,overallRiskLevel:OverallRiskLevelEnum}
     */
    public function calculate(array $issues): array
    {
        $points = 0;

        foreach ($issues as $issue) {
            $severity = (string) ($issue['severity'] ?? 'low');

            $points += match ($severity) {
                'high' => 3,
                'medium' => 2,
                default => 1,
            };
        }

        $riskScore = min(10, max(1, $points));

        $overallRiskLevel = match (true) {
            $points >= 7 => OverallRiskLevelEnum::HIGH,
            $points >= 3 => OverallRiskLevelEnum::MEDIUM,
            default => OverallRiskLevelEnum::LOW,
        };

        return [
            'riskScore' => $riskScore,
            'overallRiskLevel' => $overallRiskLevel,
        ];
    }
}
