<?php

declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\Controller;

use App\Document\Service\DocumentUploadValidator;
use App\Feature\ContractAnalyzer\Request\AnalyzeContractRequest;
use App\Feature\ContractAnalyzer\Service\AnalyzeContractService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class AnalyzeContractController extends AbstractController
{
    public function __construct(
        protected AnalyzeContractService $analyzeContractService,
        protected DocumentUploadValidator $documentUploadValidator,
    ) {
        // Nothing
    }

    #[Route('/api/contracts/analyze', name: 'app_contract_analyze', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $input = AnalyzeContractRequest::fromRequest($request);

            $this->documentUploadValidator->validate($input->file);

            $report = $this->analyzeContractService->handle($input);

            return $this->json([
                'success' => true,
                'html' => $this->renderView('contract/_report.html.twig', [
                    'report' => $report,
                ]),
                'reportTitle' => $report->documentName ?: 'Contract analysis report',
                'reportSubtitle' => 'Review the verdict, risks, and suggested fixes.',
                'riskScore' => $report->riskScore,
                'overallRisk' => $report->overallRisk,
            ], Response::HTTP_OK);
        } catch (Throwable $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
