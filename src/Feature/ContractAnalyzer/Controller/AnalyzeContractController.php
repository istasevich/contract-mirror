<?php

declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\Controller;

use App\Document\Service\DocumentUploadValidator;
use App\Feature\ContractAnalyzer\Action\ProcessContractReportAction;
use App\Feature\ContractAnalyzer\Request\AnalyzeContractRequest;
use App\Feature\ContractAnalyzer\Service\IpUsageLimiter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class AnalyzeContractController extends AbstractController
{
    public function __construct(
        protected ProcessContractReportAction $processContractReportAction,
        protected DocumentUploadValidator $documentUploadValidator,
        protected IpUsageLimiter $ipUsageLimiter,
    ) {
        // Nothing
    }

    #[Route('/api/contracts/analyze', name: 'app_contract_analyze', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $ip = $request->getClientIp() ?? 'unknown';
        $visitorId = $request->headers->get('X-Visitor-Id', 'unknown');

        $isAllowed = $this->ipUsageLimiter->isAllowed($ip, $visitorId);


        try {
            $input = AnalyzeContractRequest::fromRequest($request);

            $this->documentUploadValidator->validate($input->file);
            $result = $this->processContractReportAction->execute($input, $isAllowed);

            if ($isAllowed) {
                $this->ipUsageLimiter->increment($ip, $visitorId);
            }

            $count = $this->ipUsageLimiter->getCount($ip, $visitorId);

            $usage = [
                'used' => $count,
                'remaining' => max(0,  IpUsageLimiter::LIMIT - $count),
            ];

            if (!$isAllowed) {
                return $this->json([
                    'error' => 'Free limit reached',
                    'cta' => 'Unlock full access for $5',
                    'publicId' => $result->publicId,
                    'usage' => $usage,
                    'isLocked' => true,
                ], Response::HTTP_FORBIDDEN);
            }

            return $this->json([
                'success' => true,
                'html' => $result->html,
                'usage' => $usage,
                'publicId' => $result->publicId,
                'isLocked' => $result->isLocked,
                'reportTitle' => $result->reportTitle,
                'reportSubtitle' => $result->reportSubtitle,
                'isDemo' => false,
                'payment' => [
                    'paymentId' => $result->paymentId,
                    'address' => $result->paymentAddress,
                    'amount' => $result->paymentAmount,
                    'currency' => $result->paymentCurrency,
                ],
            ], Response::HTTP_OK);

        } catch (Throwable $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
