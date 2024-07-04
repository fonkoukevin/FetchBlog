<?php

namespace App\Controller;

use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class PostController extends AbstractController
{
    #[Route('/post', name: 'posts')]
    public function index(PostRepository $repository): Response
    {
        $posts = $repository->findAll();
        return $this->render('post/index.html.twig', [
            'controller_name' => 'PostController',
            'posts' => $posts,
            'show_navbar' => True, // Indique que la barre de navigation ne doit pas être affichée
        ]);
    }
}
