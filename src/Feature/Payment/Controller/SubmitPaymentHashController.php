<?php

declare(strict_types=1);

namespace App\Feature\Payment\Controller;

use App\Feature\Payment\Service\SubmitPaymentHashService;
use App\Feature\Payment\Service\VerifyTronPaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Throwable;

final class SubmitPaymentHashController extends AbstractController
{
    public function __construct(
        protected SubmitPaymentHashService $submitPaymentHashService,
        protected VerifyTronPaymentService $verifyTronPaymentService,
    ) {
        // Nothing
    }

    #[Route('/api/payments/{paymentId}/submit', name: 'app_payment_submit_hash', methods: ['POST'])]
    public function __invoke(string $paymentId, Request $request): JsonResponse
    {
        if (!Uuid::isValid($paymentId)) {
            throw new BadRequestHttpException('Invalid payment id');
        }

        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $txHash = (string) ($payload['txHash'] ?? '');

            $payment = $this->submitPaymentHashService->handle($paymentId, $txHash);
            $verified = $this->verifyTronPaymentService->handle($payment->getId());

            return $this->json([
                'success' => true,
                'status' => $verified ? 'confirmed' : 'pending',
            ], Response::HTTP_OK);
        } catch (Throwable $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
