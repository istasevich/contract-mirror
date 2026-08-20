<?php

declare(strict_types=1);

namespace App\Document\Parser;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface DocumentTextExtractorInterface
{
    public function supports(UploadedFile $file): bool;

    public function extract(UploadedFile $file): string;
}
