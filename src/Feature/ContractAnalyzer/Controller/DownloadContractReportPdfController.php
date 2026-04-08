<?php

declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\Controller;

use App\Feature\ContractAnalyzer\Service\GenerateContractReportPdfService;
use App\Repository\ContractReportRepository;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

final class DownloadContractReportPdfController extends AbstractController
{
    public function __construct(
        protected ContractReportRepository $reportRepository,
        protected GenerateContractReportPdfService $generateContractReportPdfService,
    ) {
        // Nothing
    }

    #[Route('/r/{publicId}/pdf', name: 'app_contract_report_pdf', methods: ['GET'])]
    public function __invoke(string $publicId): Response
    {
        $report = $this->reportRepository->findOneBy([
            'publicId' => $publicId,
        ]);

        if ($report === null) {
            throw new RuntimeException('Report not found.');
        }

        if ($report->isLocked()) {
            throw new RuntimeException('Report is locked. Please unlock full report to download.');
        }

        $pdfBinary = $this->generateContractReportPdfService->handle($report);

        $fileName = sprintf('contract-report-%s.pdf', $report->getPublicId());

        $response = new Response($pdfBinary);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $fileName,
            ),
        );

        return $response;
    }
}
