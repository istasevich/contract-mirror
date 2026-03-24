<?php declare(strict_types=1);

namespace App\Document\Parser;

use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class PdfTextExtractor implements DocumentTextExtractorInterface
{
    public function supports(UploadedFile $file): bool
    {
        return strtolower((string) $file->getClientOriginalExtension()) === 'pdf';
    }

    public function extract(UploadedFile $file): string
    {
        $pathname = $file->getPathname();

        $text = $this->extractWithPdftotext($pathname) ?? $this->extractWithRegexFallback($pathname);
        $text = $this->normalizeText($text);

        if ($text === '') {
            throw new RuntimeException('Unable to extract readable text from the PDF file.');
        }

        return $text;
    }

    private function extractWithPdftotext(string $pathname): ?string
    {
        $binary = trim((string) shell_exec('command -v pdftotext 2>/dev/null'));

        if ($binary === '') {
            return null;
        }

        $command = sprintf('%s -layout -enc UTF-8 %s - 2>/dev/null', escapeshellcmd($binary), escapeshellarg($pathname));
        $output = shell_exec($command);

        if (!is_string($output) || trim($output) === '') {
            return null;
        }

        return $output;
    }

    private function extractWithRegexFallback(string $pathname): string
    {
        $content = file_get_contents($pathname);

        if ($content === false || $content === '') {
            return '';
        }

        preg_match_all('/\(([^()]*)\)/s', $content, $matches);

        if (!isset($matches[1]) || !is_array($matches[1])) {
            return '';
        }

        $chunks = [];

        foreach ($matches[1] as $match) {
            $decoded = preg_replace('/\\([nrtbf()\\\\])/', ' $1 ', $match);
            $decoded = preg_replace('/\\[0-7]{1,3}/', ' ', (string) $decoded);
            $decoded = preg_replace('/[^\PC\s]/u', ' ', (string) $decoded);
            $decoded = trim((string) $decoded);

            if ($decoded !== '' && preg_match('/[A-Za-zА-Яа-я0-9]/u', $decoded) === 1) {
                $chunks[] = $decoded;
            }
        }

        return implode("\n", array_slice($chunks, 0, 5000));
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }
}
