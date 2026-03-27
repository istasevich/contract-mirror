<?php

declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\Service;

use App\Entity\ContractReport;
use App\Repository\ContractReportRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class CreateContractReportService
{
    public function __construct(
        protected ContractReportRepository $reportRepository,
    ) {
        // Nothing
    }

    public function handle(
        UploadedFile $file,
        string $documentType,
        string $language,
    ): ContractReport {
        $report = new ContractReport(
            publicId: $this->generatePublicId(),
            fileName: $file->getClientOriginalName() ?: 'Contract',
            fileHash: hash_file('sha256', $file->getPathname()),
            documentType: $documentType,
            language: $language,
        );

        $this->reportRepository->save($report, true);

        return $report;
    }

    protected function generatePublicId(): string
    {
        return 'CMR-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }
}
