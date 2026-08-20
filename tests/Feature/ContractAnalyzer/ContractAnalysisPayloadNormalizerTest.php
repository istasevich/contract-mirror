<?php

declare(strict_types=1);

namespace App\Tests\Feature\ContractAnalyzer;

use App\Feature\ContractAnalyzer\Support\ContractAnalysisPayloadNormalizer;
use PHPUnit\Framework\TestCase;

final class ContractAnalysisPayloadNormalizerTest extends TestCase
{
    public function testNormalizesRiskScaleSeverityAndCollections(): void
    {
        $normalized = (new ContractAnalysisPayloadNormalizer())->normalize([
            'risk_score' => 7,
            'overall_risk' => 'critical',
            'issues' => [
                ['title' => 'Payment', 'severity' => 'severe'],
                'ignored',
            ],
            'missing_protections' => [
                ['title' => 'Acceptance criteria'],
            ],
        ]);

        self::assertSame(70, $normalized['risk_score']);
        self::assertSame('MEDIUM', $normalized['overall_risk']);
        self::assertSame('MEDIUM', $normalized['issues'][0]['severity']);
        self::assertCount(1, $normalized['issues']);
        self::assertCount(1, $normalized['missing_protections']);
    }
}
