<?php declare(strict_types=1);

namespace App\Risk\DTO;

use App\Risk\Enum\OverallRiskLevelEnum;

final readonly class RiskReportDto
{
    /**
     * @param RiskIssueDto[] $issues
     * @param MissingProtectionDto[] $missingProtections
     */
    public function __construct(
        public string $documentType,
        public string $language,
        public string $summary,
        public int $riskScore,
        public OverallRiskLevelEnum $overallRiskLevel,
        public array $issues,
        public array $missingProtections,
        public string $finalRecommendation,
    ) {
        // Nothing
    }
}
