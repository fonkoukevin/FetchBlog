<?php

namespace App\Controller;

use App\Entity\Favorite;
use App\Entity\Like;
use App\Entity\Post;
use App\Repository\LikeRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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


    #[Route('/post/search', name: 'post_search', methods: ['GET'])]
    public function search(Request $request, PostRepository $repository): JsonResponse
    {
        $query = $request->query->get('q');
        $posts = $repository->findByTitleOrUsername($query);

        $results = [];
        foreach ($posts as $post) {
            $results[] = [
                'title' => $post->getTitle(),
                'username' => $post->getUser()->getUsername(),
                'image' => $post->getImage(),
                'user_image' => $post->getUser()->getImage(),
            ];
        }

        return new JsonResponse($results);
    }

    // Ajoutez cette route pour gérer les likes
    #[Route('/post/{id}/like', name: 'post_like', methods: ['POST'])]
    public function likePost(Post $post, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        // Vérifiez si l'utilisateur a déjà liké le post
        if ($post->isLikedByUser($user)) {
            return new JsonResponse(['message' => 'You have already liked this post.'], 400);
        }

        // Créez et enregistrez le like
        $like = new Like();
        $like->setUser($user);
        $like->setPost($post);
        $like->setCreatedAt(new \DateTimeImmutable());

        $em->persist($like);
        $em->flush();

        return new JsonResponse(['message' => 'Post liked successfully']);
    }

    #[Route('/post/{id}/favorite', name: 'post_favorite', methods: ['POST'])]
    public function favoritePost(Post $post, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        // Vérifiez si l'utilisateur a déjà ajouté le post en favori
        if ($post->isFavoritedByUser($user)) {
            return new JsonResponse(['message' => 'You have already favorited this post.'], 400);
        }

        // Créez et enregistrez le favori
        $favorite = new Favorite();
        $favorite->setUser($user);
        $favorite->setPost($post);
        $favorite->setCreatedAt(new \DateTimeImmutable());

        $em->persist($favorite);
        $em->flush();

        return new JsonResponse(['message' => 'Post favorited successfully']);
    }

    #[Route('/post/{id}/details', name: 'post_details', methods: ['GET'])]
    public function postDetails(Post $post): JsonResponse
    {
        $data = [
            'title' => $post->getTitle(),
            'content' => $post->getContent(),
            'image' => $post->getImage(),
            'username' => $post->getUser()->getUsername(),
            'user_image' => $post->getUser()->getImage(),
        ];

        return new JsonResponse($data);
    }

}