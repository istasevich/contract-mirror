<?php declare(strict_types=1);

namespace App\Document\Parser;

use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

final class DocxTextExtractor implements DocumentTextExtractorInterface
{
    public function supports(UploadedFile $file): bool
    {
        return strtolower((string) $file->getClientOriginalExtension()) === 'docx';
    }

    public function extract(UploadedFile $file): string
    {
        $zip = new ZipArchive();

        if ($zip->open($file->getPathname()) !== true) {
            throw new RuntimeException('Unable to open DOCX archive.');
        }

        $xmlParts = [];
        $paths = [
            'word/document.xml',
            'word/header1.xml',
            'word/header2.xml',
            'word/header3.xml',
            'word/footer1.xml',
            'word/footer2.xml',
            'word/footer3.xml',
        ];

        foreach ($paths as $path) {
            $content = $zip->getFromName($path);

            if (is_string($content) && $content !== '') {
                $xmlParts[] = $content;
            }
        }

        $zip->close();

        if ($xmlParts === []) {
            throw new RuntimeException('No readable text was found in the DOCX file.');
        }

        $text = '';

        foreach ($xmlParts as $xml) {
            $xml = preg_replace('/<w:tab\/?\s*>/i', "\t", $xml) ?? $xml;
            $xml = preg_replace('/<w:br\/?\s*>/i', "\n", $xml) ?? $xml;
            $xml = preg_replace('/<\/w:p>/i', "\n", $xml) ?? $xml;
            $text .= ' ' . strip_tags($xml);
        }

        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;
        $text = trim($text);

        if ($text === '') {
            throw new RuntimeException('No readable text was found in the DOCX file.');
        }

        return $text;
    }
}
