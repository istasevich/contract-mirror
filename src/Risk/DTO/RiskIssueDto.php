<?php

declare(strict_types=1);

namespace App\Risk\DTO;

use App\Risk\Enum\RiskSeverityEnum;

final readonly class RiskIssueDto
{
    public function __construct(
        public string $title,
        public RiskSeverityEnum $severity,
        public string $category,
        public ?string $clauseExcerpt,
        public string $plainExplanation,
        public string $whyItMatters,
        public string $impact,
        public string $riskLevelExplanation,
        public string $suggestedRewrite,
    ) {
        // Nothing
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            title: (string) ($payload['title'] ?? ''),
            severity: RiskSeverityEnum::from(strtoupper((string) ($payload['severity'] ?? 'LOW'))),
            category: (string) ($payload['category'] ?? 'other'),
            clauseExcerpt: isset($payload['original_clause'])
                ? (string) $payload['original_clause']
                : null,
            plainExplanation: (string) ($payload['plain_explanation'] ?? ''),
            whyItMatters: (string) ($payload['why_it_matters'] ?? ''),
            impact: (string) ($payload['impact'] ?? ''),
            riskLevelExplanation: (string) ($payload['risk_level_explanation'] ?? ''),
            suggestedRewrite: (string) ($payload['suggested_rewrite'] ?? ''),
        );
    }
}
