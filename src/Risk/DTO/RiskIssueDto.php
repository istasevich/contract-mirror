<?php declare(strict_types=1);

namespace App\Risk\DTO;

use App\Risk\Enum\RiskSeverityEnum;

final readonly class RiskIssueDto
{
    public function __construct(
        public string $title,
        public RiskSeverityEnum $severity,
        public string $category,
        public ?string $clauseExcerpt,
        public string $explanation,
        public string $whyItMatters,
        public string $suggestedFix,
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
            severity: RiskSeverityEnum::from((string) ($payload['severity'] ?? 'low')),
            category: (string) ($payload['category'] ?? 'other'),
            clauseExcerpt: isset($payload['clause_excerpt']) ? (string) $payload['clause_excerpt'] : null,
            explanation: (string) ($payload['explanation'] ?? ''),
            whyItMatters: (string) ($payload['why_it_matters'] ?? ''),
            suggestedFix: (string) ($payload['suggested_fix'] ?? ''),
        );
    }
}
