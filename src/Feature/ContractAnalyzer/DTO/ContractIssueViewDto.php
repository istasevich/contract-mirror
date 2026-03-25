<?php

declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\DTO;

final readonly class ContractIssueViewDto
{
    public function __construct(
        public string $title,
        public string $severity,
        public string $category,
        public string $originalClause,
        public string $plainExplanation,
        public string $whyItMatters,
        public string $suggestedRewrite,
    ) {
        // Nothing
    }
}
