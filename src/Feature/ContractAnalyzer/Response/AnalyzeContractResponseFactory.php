<?php

declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\Response;

use App\Risk\DTO\RiskReportDto;
use Symfony\Component\HttpFoundation\JsonResponse;

final class AnalyzeContractResponseFactory
{
    public function create(RiskReportDto $report): JsonResponse
    {
        return new JsonResponse([
            'documentType' => $report->documentType,
            'language' => $report->language,

            'summary' => $report->summary,
            'riskScore' => $report->riskScore,
            'overallRiskLevel' => $report->overallRiskLevel->value,

            'riskSummary' => $report->riskSummary,

            'issues' => array_map(static fn ($issue) => [
                'title' => $issue->title,
                'severity' => $issue->severity->value,
                'category' => $issue->category,

                'clauseExcerpt' => $issue->clauseExcerpt,

                // 🔥 ключевые продающие поля
                'plainExplanation' => $issue->plainExplanation,
                'whyItMatters' => $issue->whyItMatters,
                'impact' => $issue->impact,

                'riskExplanation' => $issue->riskLevelExplanation,

                'suggestedRewrite' => $issue->suggestedRewrite,
            ], $report->issues),

            'missingProtections' => array_map(static fn ($item) => [
                'title' => $item->title,
                'category' => $item->category ?? null,
                'whyItMatters' => $item->whyItMatters,
                'suggestedClause' => $item->suggestedClause,
            ], $report->missingProtections),

            'finalRecommendation' => $report->finalRecommendation,
        ]);
    }
}
