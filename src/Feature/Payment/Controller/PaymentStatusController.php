<?php

declare(strict_types=1);

namespace App\Feature\Payment\Controller;

use App\Repository\ReportPaymentRepository;
use App\Feature\Payment\Service\VerifyTronPaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class PaymentStatusController extends AbstractController
{
    public function __construct(
        protected ReportPaymentRepository $paymentRepository,
        protected VerifyTronPaymentService $verifyTronPaymentService,
    ) {
        // Nothing
    }

    #[Route('/api/payments/{paymentId}/status', name: 'app_payment_status', methods: ['GET'])]
    public function __invoke(string $paymentId): JsonResponse
    {
        try {
            $payment = $this->paymentRepository->findOneById($paymentId);

            if ($payment === null) {
                return $this->json([
                    'success' => false,
                    'message' => 'Payment not found.',
                ], Response::HTTP_NOT_FOUND);
            }

            if ($payment->getStatus() !== 'confirmed' && $payment->getTxHash() !== null) {
                $this->verifyTronPaymentService->handle($paymentId);
                $payment = $this->paymentRepository->findOneById($paymentId);
            }

            return $this->json([
                'success' => true,
                'status' => $payment?->getStatus() ?? 'pending',
                'reportUnlocked' => $payment?->getReport()->isLocked() === false,
            ], Response::HTTP_OK);
        } catch (Throwable $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
