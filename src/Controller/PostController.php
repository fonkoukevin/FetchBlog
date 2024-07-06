<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Favorite;
use App\Entity\Like;
use App\Entity\Post;
use App\Form\PostType;
use App\Repository\CommentRepository;
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


// src/Controller/PostController.php

    #[Route('/post/search', name: 'post_search', methods: ['GET'])]
    public function search(Request $request, PostRepository $repository): Response
    {
        $query = $request->query->get('q');
        $posts = $repository->findByTitleOrUsername($query);

        $html = '';
        foreach ($posts as $post) {
            $html .= $this->renderView('post/_post.html.twig', [
                'post' => $post,
            ]);
        }

        return new Response($html);
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



    #[Route('/post/{id}/comments', name: 'post_comments', methods: ['GET', 'POST'])]
    public function postComments(Post $post, Request $request, EntityManagerInterface $em, CommentRepository $commentRepository): JsonResponse
    {
        if ($request->isMethod('POST')) {
            $data = json_decode($request->getContent(), true);
            $content = $data['content'] ?? '';

            if (empty($content)) {
                return new JsonResponse(['error' => 'Content cannot be empty'], 400);
            }

            $comment = new Comment();
            $comment->setContent($content);
            $comment->setCreatedAt(new \DateTimeImmutable());
            $comment->setUser($this->getUser());
            $comment->setPost($post);

            $em->persist($comment);
            $em->flush();

            return new JsonResponse(['message' => 'Comment added successfully']);
        }

        $comments = $commentRepository->findBy(['post' => $post], ['createdAt' => 'DESC']);
        $commentsData = [];

        foreach ($comments as $comment) {
            $commentsData[] = [
                'username' => $comment->getUser()->getUsername(),
                'content' => $comment->getContent(),
                'createdAt' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
                'user_image' => $comment->getUser()->getImage() ?? 'default.png', // Utilisez une image par défaut si l'image de l'utilisateur n'est pas définie
            ];
        }

        return new JsonResponse($commentsData);
    }





    #[Route('/post/new', name: 'new_post', methods: ['POST'])]
    public function newPost(Request $request, EntityManagerInterface $entityManager): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setUser($this->getUser());
            $post->setCreatedAt(new \DateTimeImmutable());
            $post->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->persist($post);
            $entityManager->flush();

            return $this->redirectToRoute('user_posts');
        }

        return $this->render('post/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/post/favorites', name: 'favorite_posts')]
    public function favoritePosts(): Response
    {
        $user = $this->getUser();
        $favorites = $user->getFavorites();

        return $this->render('post/favorites.html.twig', [
            'favorites' => $favorites,
        ]);
    }

    #[Route('/post/user', name: 'user_posts')]
    public function userPosts(PostRepository $postRepository): Response
    {
        $user = $this->getUser();
        $posts = $postRepository->findBy(['user' => $user]);

        return $this->render('post/user_posts.html.twig', [
            'posts' => $posts,
        ]);
    }
}