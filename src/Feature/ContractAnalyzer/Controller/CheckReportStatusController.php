<?php

declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\Controller;

use App\Repository\ContractReportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class CheckReportStatusController extends AbstractController
{
    public function __construct(
        protected ContractReportRepository $reportRepository,
    ) {
        // Nothing
    }

    #[Route('/api/reports/{publicId}/status', name: 'app_report_status', methods: ['GET'])]
    public function __invoke(string $publicId): JsonResponse
    {
        $report = $this->reportRepository->findOneByPublicId($publicId);

        if ($report === null) {
            return $this->json([
                'error' => 'Report not found',
            ], 404);
        }

        return $this->json([
            'isLocked' => $report->isLocked(),
        ]);
    }
}
