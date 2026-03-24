<?php declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class RewriteClauseController
{
    #[Route('/api/contracts/rewrite-clause', name: 'api_contracts_rewrite_clause', methods: ['POST'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Not implemented yet.',
        ], 501);
    }
}
