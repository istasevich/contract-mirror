<?php

declare(strict_types=1);

namespace App\Tests\Risk;

use App\Risk\Enum\OverallRiskLevelEnum;
use App\Risk\Service\RiskScoreCalculator;
use PHPUnit\Framework\TestCase;

final class RiskScoreCalculatorTest extends TestCase
{
    public function testCalculatesBoundedRiskScoreAndLevel(): void
    {
        $result = (new RiskScoreCalculator())->calculate([
            ['severity' => 'high'],
            ['severity' => 'medium'],
            ['severity' => 'low'],
            ['severity' => 'high'],
        ]);

        self::assertSame(9, $result['riskScore']);
        self::assertSame(OverallRiskLevelEnum::HIGH, $result['overallRiskLevel']);
    }

    public function testDefaultsUnknownSeverityToLowWeight(): void
    {
        $result = (new RiskScoreCalculator())->calculate([
            ['severity' => 'unknown'],
        ]);

        self::assertSame(1, $result['riskScore']);
        self::assertSame(OverallRiskLevelEnum::LOW, $result['overallRiskLevel']);
    }
}
