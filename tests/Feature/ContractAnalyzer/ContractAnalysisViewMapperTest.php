<?php

declare(strict_types=1);

namespace App\Tests\Feature\ContractAnalyzer;

use App\Feature\ContractAnalyzer\Mapper\ContractAnalysisViewMapper;
use PHPUnit\Framework\TestCase;

final class ContractAnalysisViewMapperTest extends TestCase
{
    public function testMapsNormalizedPayloadToViewDto(): void
    {
        $view = (new ContractAnalysisViewMapper())->map(
            payload: [
                'risk_score' => 80,
                'overall_risk' => 'high',
                'executive_summary' => 'Summary',
                'issues' => [
                    ['title' => 'Late payment', 'severity' => 'high'],
                ],
                'missing_protections' => [
                    ['title' => 'Kill fee'],
                ],
            ],
            documentName: 'contract.pdf',
            documentType: 'PDF',
            language: 'en',
        );

        self::assertSame('contract.pdf', $view->documentName);
        self::assertSame(80, $view->riskScore);
        self::assertSame('HIGH', $view->overallRisk);
        self::assertSame('Late payment', $view->issues[0]->title);
        self::assertSame('Kill fee', $view->missingProtections[0]->title);
    }
}
