<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AproposController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    #[Route('/apropos', name: 'apropos')]
    public function index(): Response
    {
        return $this->render('apropos/index.html.twig', [
            'controller_name' => 'AproposController',
        ]);
    }
}
