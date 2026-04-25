<?php

declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PaywallController extends AbstractController
{
    #[Route('/api/paywall/{publicId}', name: 'app_paywall', methods: ['GET'])]
    public function __invoke(?string $publicId = null): Response
    {
        $publicId = $publicId ?: 'demo';

        $html = $this->renderView('contract/_paywall.html.twig', [
            'publicId' => $publicId,
        ]);

        return new Response($html, Response::HTTP_OK, [
            'Content-Type' => 'text/html',
        ]);
    }
}
