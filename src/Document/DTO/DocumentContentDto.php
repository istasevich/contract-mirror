<?php

declare(strict_types=1);

namespace App\Document\DTO;

use App\Document\Enum\DocumentTypeEnum;

final readonly class DocumentContentDto
{
    public function __construct(
        public string $originalName,
        public DocumentTypeEnum $documentType,
        public string $content,
        public ?string $detectedLanguage = null,
    ) {
        // Nothing
    }
}
