<?php

declare(strict_types=1);

namespace App\Feature\Payment\Controller;

use App\Feature\Payment\Webhook\WebhookVerifierInterface;
use App\Repository\ContractReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GumroadWebhookController extends AbstractController
{
    public function __construct(
        protected ContractReportRepository $reportRepository,
        protected EntityManagerInterface $em,
        private readonly WebhookVerifierInterface $webhookVerifier,
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $logger,
    ) {
        // Nothing
    }

    #[Route('/api/payments/gumroad/webhook', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $data = $request->request->all();
        $verification = $this->webhookVerifier->verify($data);

        if (!$verification->isValid || $verification->publicId === null) {
            $this->logger->warning('Gumroad webhook rejected.', [
                'reason' => $verification->reason,
            ]);

            return new Response('Invalid webhook', Response::HTTP_FORBIDDEN);
        }

        if ($verification->providerEventId !== null && $this->isDuplicate($verification->providerEventId)) {
            return new Response('OK', Response::HTTP_OK);
        }

        $report = $this->reportRepository->findOneByPublicId($verification->publicId);

        if ($report === null) {
            $this->logger->notice('Gumroad webhook referenced an unknown report.', [
                'public_id_hash' => hash('sha256', $verification->publicId),
            ]);

            return new Response('OK', Response::HTTP_OK);
        }

        if (!$report->isLocked()) {
            return new Response('OK', Response::HTTP_OK);
        }

        $report->unlock();

        $this->em->flush();

        return new Response('OK', Response::HTTP_OK);
    }

    private function isDuplicate(string $providerEventId): bool
    {
        $item = $this->cache->getItem('gumroad_webhook_' . hash('sha256', $providerEventId));

        if ($item->isHit()) {
            return true;
        }

        $item->set(true);
        $item->expiresAfter(31 * 86400);
        $this->cache->save($item);

        return false;
    }
}
