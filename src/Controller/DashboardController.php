<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Status;
use App\Entity\Subscription;
use App\Entity\User;
use App\Form\PostType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    // DashboardController.php

    #[Route('/dashboard', name: 'dashboard')]
    public function index(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);

        // Handle the form submission
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setCreatedAt(new \DateTimeImmutable());
            $post->setUpdatedAt(new \DateTimeImmutable());
            $post->setUser($user);

            // Handle image upload
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/images',
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Handle the exception appropriately
                    $this->addFlash('error', 'Image upload failed');
                }

                $post->setImage($newFilename);
            }

            $entityManager->persist($post);
            $entityManager->flush();

            return $this->redirectToRoute('dashboard');
        }

        // Retrieve the number of favorites
        $favoriteCount = $entityManager->getRepository('App\Entity\Favorite')->countFavoritesByUser($user);

        // Retrieve the number of likes
        $likeCount = $entityManager->getRepository('App\Entity\Like')->countLikesByUser($user);
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
        return $this->render('dashboard/index.html.twig', [
            'controller_name' => 'DashboardController',
            'show_navbar' => true,
            'user' => $user,
            'postForm' => $form->createView(),
            'favoriteCount' => $favoriteCount,
            'likeCount' => $likeCount,
            'posts' => $user->getPosts(),// Ensure posts are passed to the template
            'currentUserId' => $user->getId(),
            'isAdmin' => $isAdmin,
        ]);
    }

    #[Route('/dashboard/favorites', name: 'dashboard_favorites', methods: ['GET'])]
    public function getFavorites(): JsonResponse
    {
        $user = $this->getUser();
        $favorites = $user->getFavorites();

        $favoritesData = [];
        foreach ($favorites as $favorite) {
            $favoritesData[] = [
                'title' => $favorite->getPost()->getTitle(),
                'content' => $favorite->getPost()->getContent(),
                'image' => $favorite->getPost()->getImage(),
                'username' => $favorite->getPost()->getUser()->getUsername(),
            ];
        }

        return new JsonResponse($favoritesData);
    }

    #[Route('/dashboard/posts', name: 'dashboard_posts', methods: ['GET'])]
    public function getPosts(): JsonResponse
    {
        $user = $this->getUser();
        $posts = $user->getPosts();

        $postsData = [];
        foreach ($posts as $post) {
            $postsData[] = [
                'id' => $post->getId(),
                'title' => $post->getTitle(),
                'content' => $post->getContent(),
                'image' => $post->getImage(),
                'username' => $post->getUser()->getUsername(),
            ];
        }

        return new JsonResponse($postsData);
    }


    #[Route('/dashboard/subscriptions', name: 'dashboard_subscriptions', methods: ['GET'])]
    public function getSubscriptions(EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();

        // Abonnements (utilisateurs auxquels l'utilisateur est abonné)
        $subscriptions = $entityManager->getRepository(Subscription::class)
            ->findBy(['subscriber' => $user]);

        $subscriptionsData = [];
        foreach ($subscriptions as $subscription) {
            $subscribedTo = $subscription->getSubscribedTo();
            $subscriptionsData[] = [
                'username' => $subscribedTo->getUsername(),
                'user_image' => $subscribedTo->getImage(),
            ];
        }

        // Abonnés (utilisateurs qui sont abonnés à l'utilisateur)
        $subscribers = $entityManager->getRepository(Subscription::class)
            ->findBy(['subscribedTo' => $user]);

        $subscribersData = [];
        foreach ($subscribers as $subscriber) {
            $subscriberUser = $subscriber->getSubscriber();
            $subscribersData[] = [
                'username' => $subscriberUser->getUsername(),
                'user_image' => $subscriberUser->getImage(),
            ];
        }

        return new JsonResponse([
            'subscriptions' => $subscriptionsData,
            'subscribers' => $subscribersData,
        ]);
    }
    #[Route('/dashboard/edit/{id}', name: 'dashboard_edit', methods: ['GET', 'POST'], requirements: ['id' => Requirement::DIGITS] )]
    public function edit(Request $request, Post $post, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setUpdatedAt(new \DateTimeImmutable());

            // Handle image upload if a new image is provided
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/images',
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Handle the exception appropriately
                    $this->addFlash('error', 'Image upload failed');
                }

                $post->setImage($newFilename);
            }

            $entityManager->flush();

            return $this->redirectToRoute('dashboard');
        }

        return $this->render('dashboard/edit.html.twig', [
            'postForm' => $form->createView(),
            'post' => $post,
        ]);
    }


    #[Route('/dashboard/delete/{id}', name: 'dashboard_delete', methods: ['POST'])]
    public function delete(Post $post, EntityManagerInterface $entityManager): JsonResponse
    {
        $status = $entityManager->getRepository(Status::class)->findOneBy(['name' => 'supprimer']);

        if ($status) {
            $post->setStatus($status);
            $entityManager->flush();

            return new JsonResponse(['status' => 'success']);
        }

        return new JsonResponse(['status' => 'error'], Response::HTTP_BAD_REQUEST);
    }


    #[Route('/dashboard/{id}', name: 'user_dashboard', requirements: ['id' => '\d+'])]
    public function userDashboard(User $user, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $currentUser = $this->getUser();

        // Retrieve the number of favorites
        $favoriteCount = $entityManager->getRepository('App\Entity\Favorite')->countFavoritesByUser($user);

        // Retrieve the number of likes
        $likeCount = $entityManager->getRepository('App\Entity\Like')->countLikesByUser($user);
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
        return $this->render('dashboard/index.html.twig', [
            'controller_name' => 'DashboardController',
            'show_navbar' => true,
            'user' => $user,
            'isAdmin' => $isAdmin,
            'favoriteCount' => $favoriteCount,
            'likeCount' => $likeCount,
            'posts' => $user->getPosts(), // Ensure posts are passed to the template
            'currentUserId' => $currentUser ? $currentUser->getId() : null,
            'postForm' => $currentUser && $currentUser->getId() === $user->getId() ? $this->createForm(PostType::class)->createView() : null,

            ]);
    }

}





