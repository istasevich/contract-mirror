<?php

declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\Controller;

use App\Document\Service\DocumentUploadValidator;
use App\Feature\ContractAnalyzer\Action\ProcessContractReportAction;
use App\Feature\ContractAnalyzer\Request\AnalyzeContractRequest;
use App\Feature\ContractAnalyzer\Service\IpUsageLimiter;
use App\Shared\Exception\ExternalServiceException;
use App\Shared\Exception\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

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
        $userAgent = $request->headers->get('User-Agent');

        try {
            $input = AnalyzeContractRequest::fromRequest($request);

            $this->documentUploadValidator->validate($input->file);

            if (!$this->ipUsageLimiter->reserveUsage($ip, $visitorId, $userAgent)) {
                $count = $this->ipUsageLimiter->getCount($ip, $visitorId, $userAgent);

                return $this->json([
                    'success' => false,
                    'error' => 'free_limit_reached',
                    'message' => 'Free limit reached.',
                    'cta' => 'Unlock full access for $5',
                    'usage' => [
                        'used' => $count,
                        'remaining' => 0,
                    ],
                    'isLocked' => true,
                ], Response::HTTP_TOO_MANY_REQUESTS);
            }

            $result = $this->processContractReportAction->execute($input);
            $count = $this->ipUsageLimiter->getCount($ip, $visitorId, $userAgent);

            $usage = [
                'used' => $count,
                'remaining' => max(0, IpUsageLimiter::LIMIT - $count),
            ];

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

        } catch (InvalidArgumentException $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (ExternalServiceException) {
            return $this->json([
                'success' => false,
                'message' => 'Contract analysis is temporarily unavailable.',
            ], Response::HTTP_BAD_GATEWAY);
        } catch (HttpExceptionInterface $exception) {
            return $this->json([
                'success' => false,
                'message' => Response::$statusTexts[$exception->getStatusCode()] ?? 'Request failed.',
            ], $exception->getStatusCode());
        } catch (\Throwable) {
            return $this->json([
                'success' => false,
                'message' => 'Unable to analyze the contract.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
