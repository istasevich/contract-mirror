<?php declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\Controller;

use App\Document\Service\DocumentUploadValidator;
use App\Feature\ContractAnalyzer\DTO\AnalyzeContractCommand;
use App\Feature\ContractAnalyzer\Response\AnalyzeContractResponseFactory;
use App\Feature\ContractAnalyzer\Service\AnalyzeContractService;
use App\Shared\Exception\InvalidArgumentException;
use Throwable;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class AnalyzeContractController
{
    public function __construct(
        protected AnalyzeContractService $service,
        protected AnalyzeContractResponseFactory $responseFactory,
        protected DocumentUploadValidator $documentUploadValidator,
    ) {
        // Nothing
    }

    #[Route('/api/contracts/analyze', name: 'api_contracts_analyze', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $file = $this->documentUploadValidator->validate($request->files->get('file'));

            $command = new AnalyzeContractCommand(
                file: $file,
                preferredLanguage: (string) $request->request->get('preferredLanguage', 'en'),
            );

            $report = $this->service->run($command);

            return $this->responseFactory->create($report);
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            return new JsonResponse([
                'message' => 'Unable to analyze the contract right now. Please try again.',
                'details' => $request->query->getBoolean('debug') ? $exception->getMessage() : null,
            ], 500);
        }
    }

    #[Route('/health', name: 'health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'service' => 'contractmirror',
        ]);
    }
}
