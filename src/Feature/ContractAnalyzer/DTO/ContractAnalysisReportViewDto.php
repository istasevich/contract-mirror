<?php

namespace App\Feature\ContractAnalyzer\DTO;

final readonly class ContractAnalysisReportViewDto
{
    /**
     * @param ContractIssueViewDto[] $issues
     * @param MissingProtectionViewDto[] $missingProtections
     */
    public function __construct(
        public string $documentName,
        public string $documentType,
        public string $language,
        public int $riskScore,
        public string $overallRisk,
        public string $riskSummary,
        public string $executiveSummary,
        public string $finalRecommendation,
        public string $signingRecommendation,
        public array $issues,
        public array $missingProtections,
    ) {
        // Nothing
    }
}
