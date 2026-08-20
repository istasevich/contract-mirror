<?php

declare(strict_types=1);

namespace App\Feature\ContractAnalyzer\Controller;

use App\Feature\ContractAnalyzer\Support\FakeContractReportFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LandingController extends AbstractController
{
    public function __construct(
        protected FakeContractReportFactory $fakeReportFactory,
    ) {
        // Nothing
    }

    #[Route('/', name: 'app_landing')]
    public function __invoke(): Response
    {
        $fakeReport = $this->fakeReportFactory->make();

        $initialReportHtml = $this->renderView(
            'contract/_report.html.twig',
            [
                'report' => $fakeReport,
                'isDemo' => true,
                'publicId' => null,
                'isLocked' => false,
            ]
        );
        return $this->render('landing/index.html.twig', [
            'initialReportHtml' => $initialReportHtml,
            'initialReportTitle' => $fakeReport->documentName,
            'initialReportSubtitle' => 'Demo report — upload your contract to get your own analysis',
            'initialPublicId' => null,
            'publicId' => null,
            'isDemo' => true,
        ]);
    }
}
