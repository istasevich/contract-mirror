<?php

namespace App\Feature\ContractAnalyzer\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LandingController extends AbstractController
{
    #[Route('/', name: 'landing', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('landing/index.html.twig');
    }
}
