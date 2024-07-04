<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(Security $security): Response
    {
//        dd($security->getUser());
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'show_navbar' => false, // Indique que la barre de navigation ne doit pas être affichée

        ]);
    }
}
