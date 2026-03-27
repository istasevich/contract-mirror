<?php

declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\Service;

use App\Feature\ContractAnalyzer\Dto\ContractAnalysisReportViewDto;
use App\Feature\ContractAnalyzer\Mapper\ContractAnalysisViewMapper;
use App\Feature\ContractAnalyzer\Request\AnalyzeContractRequest;
use App\Feature\ContractAnalyzer\Prompt\AnalyzeContractPromptFactory;
use App\Feature\ContractAnalyzer\Support\ContractAnalysisPayloadNormalizer;
use App\Feature\ContractAnalyzer\Support\FakeContractReportFactory;
use App\Shared\AI\LlmClientInterface;
use App\Shared\Document\ContractDocumentTextExtractorInterface;
use RuntimeException;

final class AnalyzeContractService
{
    private const int MAX_CONTRACT_LENGTH = 30000;

    public function __construct(
        protected ContractDocumentTextExtractorInterface $documentTextExtractor,
        protected AnalyzeContractPromptFactory $promptFactory,
        protected FakeContractReportFactory $fakeContractReportFactory,
        protected LlmClientInterface $llmClient,
        protected ContractAnalysisPayloadNormalizer $payloadNormalizer,
        protected ContractAnalysisViewMapper $viewMapper,
    ) {
        // Nothing
    }

    public function handle(AnalyzeContractRequest $request): ContractAnalysisReportViewDto
    {
        if ($_ENV['CONTRACT_MIRROR_FAKE_REPORT'] ?? false) {
            return $this->fakeContractReportFactory->make();
        }

        if ($request->file === null) {
            throw new RuntimeException('Contract file is required.');
        }

        $documentName = $request->file->getClientOriginalName() ?: 'Contract';
        $documentType = $this->resolveDocumentType($request->file->getClientOriginalExtension());

        $contractText = $this->documentTextExtractor->extract($request->file);

        $contractText = $this->normalizeText($contractText->content);

        if ($contractText === '') {
            throw new RuntimeException('Could not extract readable text from the uploaded file.');
        }

        $contractText = $this->truncateText($contractText);

        $prompt = $this->promptFactory->build(
            contractText: $contractText,
            language: $request->preferredLanguage,
        );

        $rawPayload = $this->llmClient->generateStructured($prompt);

        if (!is_array($rawPayload)) {
            throw new RuntimeException('Model returned invalid analysis payload.');
        }

        $normalizedPayload = $this->payloadNormalizer->normalize($rawPayload);

        return $this->viewMapper->map(
            payload: $normalizedPayload,
            documentName: $documentName,
            documentType: $documentType,
            language: $request->preferredLanguage,
        );
    }

    protected function normalizeText(string $text): string
    {
        $text = @preg_replace('/[ \t]+/u', ' ', $text) ?: $text;

        return trim($text);
    }

    protected function truncateText(string $text): string
    {
        if (mb_strlen($text) <= self::MAX_CONTRACT_LENGTH) {
            return $text;
        }

        return mb_substr($text, 0, self::MAX_CONTRACT_LENGTH);
    }

    protected function resolveDocumentType(?string $extension): string
    {
        return match (mb_strtolower((string) $extension)) {
            'pdf' => 'PDF',
            'doc' => 'DOC',
            'docx' => 'DOCX',
            'txt' => 'TXT',
            default => 'Document',
        };
    }
}
