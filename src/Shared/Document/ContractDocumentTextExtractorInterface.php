<?php

declare(strict_types=1);

namespace App\Shared\Document;

use App\Document\DTO\DocumentContentDto;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface ContractDocumentTextExtractorInterface
{
    public function extract(UploadedFile $file): DocumentContentDto;
}
