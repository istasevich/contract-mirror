<?php

declare(strict_types=1);

namespace App\Document\Parser;

use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class PlainTextExtractor implements DocumentTextExtractorInterface
{
    public function supports(UploadedFile $file): bool
    {
        return strtolower((string) $file->getClientOriginalExtension()) === 'txt';
    }

    public function extract(UploadedFile $file): string
    {
        $content = file_get_contents($file->getPathname());

        if ($content === false) {
            throw new RuntimeException('Unable to read the uploaded text file.');
        }

        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $content = preg_replace('/\n{3,}/', "\n\n", $content) ?? $content;

        return trim($content);
    }
}
