<?php

declare(strict_types=1);

namespace App\Feature\Payment\Controller;

use App\Repository\ContractReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GumroadWebhookController extends AbstractController
{
    public function __construct(
        protected ContractReportRepository $reportRepository,
        protected EntityManagerInterface $em,
    ) {
        // Nothing
    }

    #[Route('/api/payments/gumroad/webhook', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $data = $request->request->all();

        file_put_contents(
            $this->getParameter('kernel.project_dir') . '/var/log/gumroad.log',
            json_encode($data, JSON_UNESCAPED_UNICODE) . PHP_EOL,
            FILE_APPEND
        );

        $params = $data['url_params'] ?? [];

        $publicId = $params['custom'] ?? null;

        if (!$publicId) {
            return new Response('No publicId', 200);
        }

        $report = $this->reportRepository->findOneByPublicId($publicId);

        if (!$report) {
            return new Response('Report not found', 200);
        }


        if (!$report->isLocked()) {
            return new Response('Already unlocked', 200);
        }

        $report->unlock();

        $this->em->flush();

        return new Response('OK', 200);
    }
}
