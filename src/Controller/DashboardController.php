<?php

namespace App\Controller;

use App\Entity\Post;
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
use Symfony\Component\String\Slugger\SluggerInterface;

class DashboardController extends AbstractController
{
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

        return $this->render('dashboard/index.html.twig', [
            'controller_name' => 'DashboardController',
            'show_navbar' => true,
            'user' => $user,
            'postForm' => $form->createView(),
            'favoriteCount' => $favoriteCount,
            'likeCount' => $likeCount,
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

}





