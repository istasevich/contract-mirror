<?php

declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\Controller;

use App\Feature\ContractAnalyzer\Application\RewriteClauseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class RewriteClauseController extends AbstractController
{
    public function __construct(
        protected RewriteClauseService $rewriteClauseService,
    ) {
        // Nothing
    }

    #[Route('/api/contracts/rewrite-clause', name: 'app_contract_rewrite_clause', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $clause = trim((string) ($payload['clause'] ?? ''));
            $context = trim((string) ($payload['context'] ?? ''));
            $language = trim((string) ($payload['language'] ?? 'English'));

            if ($clause === '') {
                return $this->json([
                    'success' => false,
                    'message' => 'Clause is required.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $result = $this->rewriteClauseService->handle(
                clause: $clause,
                context: $context,
                language: $language,
            );

            return $this->json([
                'success' => true,
                'rewrite' => $result['rewrite'],
                'reasoning' => $result['reasoning'],
            ]);
        } catch (Throwable $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
