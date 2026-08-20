<?php

declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\DTO;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class AnalyzeContractCommand
{
    public function __construct(
        public UploadedFile $file,
        public string $preferredLanguage,
    ) {
        // Nothing
    }
}
