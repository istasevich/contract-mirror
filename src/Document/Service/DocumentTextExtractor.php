<?php declare(strict_types=1);

namespace App\Document\Service;

use App\Document\DTO\DocumentContentDto;
use App\Document\Enum\DocumentTypeEnum;
use App\Document\Parser\DocumentTextExtractorInterface;
use App\Shared\Document\ContractDocumentTextExtractorInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class DocumentTextExtractor implements ContractDocumentTextExtractorInterface
{
    private const int MAX_TEXT_LENGTH = 50000;

    /**
     * @param iterable<DocumentTextExtractorInterface> $extractors
     */
    public function __construct(
        protected iterable $extractors,
    ) {
        // Nothing
    }

    public function extract(UploadedFile $file): DocumentContentDto
    {
        foreach ($this->extractors as $extractor) {
            if (!$extractor->supports($file)) {
                continue;
            }

            $content = $this->normalizeText($extractor->extract($file));

            if ($content === '') {
                throw new RuntimeException('The uploaded file does not contain readable text.');
            }

            return new DocumentContentDto(
                originalName: $file->getClientOriginalName(),
                documentType: $this->resolveDocumentType($file),
                content: mb_substr($content, 0, self::MAX_TEXT_LENGTH),
            );
        }

        throw new RuntimeException('Unsupported document type.');
    }

    protected function resolveDocumentType(UploadedFile $file): DocumentTypeEnum
    {
        return match (strtolower((string) $file->getClientOriginalExtension())) {
            'pdf' => DocumentTypeEnum::PDF,
            'docx' => DocumentTypeEnum::DOCX,
            'txt' => DocumentTypeEnum::TXT,
            default => DocumentTypeEnum::UNKNOWN,
        };
    }

    private function normalizeText(string $content): string
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $content = preg_replace('/[ \t]+/', ' ', $content) ?? $content;
        $content = preg_replace('/\n{3,}/', "\n\n", $content) ?? $content;

        return trim($content);
    }
}
