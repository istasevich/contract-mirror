<?php

declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\DTO;

final readonly class MissingProtectionViewDto
{
    public function __construct(
        public string $title,
        public string $category,
        public string $whyItMatters,
        public string $suggestedClause,
    ) {
        // Nothing
    }
}
