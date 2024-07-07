<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Favorite;
use App\Entity\Like;
use App\Entity\Post;
use App\Entity\Subscription;
use App\Entity\User;
use App\Form\PostType;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use App\Repository\LikeRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
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
    // src/Controller/PostController.php

    #[Route('/post', name: 'posts')]
    public function index(PostRepository $repository, UserRepository $userRepository, CategoryRepository $categoryRepository): Response
    {
        $statusId = 1; // ID du statut "créé"
        $posts = $repository->findByStatus($statusId);
        $topUsers = $userRepository->findTopUsersBySubscribers();
        $categories = $categoryRepository->findAll();

        return $this->render('post/index.html.twig', [
            'posts' => $posts,
            'topUsers' => $topUsers,
            'categories' => $categories,
            'show_navbar' => true,
        ]);
    }


    #[Route('/add-friend/{id}', name: 'add_friend', methods: ['POST'])]
    public function addFriend(User $user, EntityManagerInterface $em): Response
    {
        $currentUser = $this->getUser();

        if ($user === $currentUser) {
            return $this->redirectToRoute('posts');
        }

        // Vérifiez si l'abonnement existe déjà
        $existingSubscription = $em->getRepository(Subscription::class)->findOneBy([
            'subscriber' => $currentUser,
            'subscribedTo' => $user
        ]);

        if ($existingSubscription) {
            return $this->redirectToRoute('posts');
        }

        $subscription = new Subscription();
        $subscription->setSubscriber($currentUser);
        $subscription->setSubscribedTo($user);
        $subscription->setCreatedAt(new \DateTimeImmutable());

        $em->persist($subscription);
        $em->flush();

        return $this->redirectToRoute('posts');
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
    public function likePost(Post $post, EntityManagerInterface $em, LikeRepository $likeRepository): JsonResponse
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

        // Compter le nombre de likes
        $likeCount = $likeRepository->count(['post' => $post]);

        return new JsonResponse(['message' => 'Post liked successfully', 'likeCount' => $likeCount]);
    }
    #[Route('/post/{id}/unlike', name: 'post_unlike', methods: ['POST'])]
    public function unlikePost(Post $post, EntityManagerInterface $em, LikeRepository $likeRepository): JsonResponse
    {
        $user = $this->getUser();

        // Trouvez le like de l'utilisateur pour ce post
        $like = $likeRepository->findOneBy(['post' => $post, 'user' => $user]);

        if (!$like) {
            return new JsonResponse(['message' => 'You have not liked this post.'], 400);
        }

        $em->remove($like);
        $em->flush();

        // Compter le nombre de likes
        $likeCount = $likeRepository->count(['post' => $post]);

        return new JsonResponse(['message' => 'Post unliked successfully', 'likeCount' => $likeCount]);
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
        $categories = [];
        foreach ($post->getCategories() as $category) {
            $categories[] = $category->getName();
        }

        $data = [
            'title' => $post->getTitle(),
            'content' => $post->getContent(),
            'image' => $post->getImage(),
            'username' => $post->getUser()->getUsername(),
            'user_image' => $post->getUser()->getImage(),
            'categories' => $categories,
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


    // src/Controller/PostController.php

    #[Route('/post/filter', name: 'post_filter', methods: ['GET'])]
    public function filter(Request $request, PostRepository $postRepository, CategoryRepository $categoryRepository): Response
    {
        $categoryId = $request->query->get('category_id');

        // If no category is selected, return all posts
        if (!$categoryId) {
            $posts = $postRepository->findAll();
        } else {
            $category = $categoryRepository->find($categoryId);
            $posts = $category ? $category->getPosts() : [];
        }

        $html = '';
        foreach ($posts as $post) {
            $html .= $this->renderView('post/_post.html.twig', [
                'post' => $post,
            ]);
        }

        return new Response($html);
    }

}