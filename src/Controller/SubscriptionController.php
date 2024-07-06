<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SubscriptionController extends AbstractController
{
    #[Route('/subscribe/{id}', name: 'subscribe', methods: ['POST'])]
    public function subscribe(User $userToSubscribe, EntityManagerInterface $em): JsonResponse
    {
        $currentUser = $this->getUser();

        if ($currentUser === $userToSubscribe) {
            return new JsonResponse(['message' => 'You cannot subscribe to yourself.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        foreach ($currentUser->getSubscriptions() as $subscription) {
            if ($subscription->getSubscribedTo() === $userToSubscribe) {
                return new JsonResponse(['message' => 'You are already subscribed to this user.'], JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        $subscription = new Subscription();
        $subscription->setSubscriber($currentUser);
        $subscription->setSubscribedTo($userToSubscribe);
        $em->persist($subscription);
        $em->flush();

        return new JsonResponse(['message' => 'Subscribed successfully.']);
    }

    #[Route('/unsubscribe/{id}', name: 'unsubscribe', methods: ['POST'])]
    public function unsubscribe(User $userToUnsubscribe, EntityManagerInterface $em): JsonResponse
    {
        $currentUser = $this->getUser();

        foreach ($currentUser->getSubscriptions() as $subscription) {
            if ($subscription->getSubscribedTo() === $userToUnsubscribe) {
                $em->remove($subscription);
                $em->flush();

                return new JsonResponse(['message' => 'Unsubscribed successfully.']);
            }
        }

        return new JsonResponse(['message' => 'You are not subscribed to this user.'], JsonResponse::HTTP_BAD_REQUEST);
    }

    #[Route('/user/network', name: 'user_network')]
    public function network(): Response
    {
        $user = $this->getUser();
        $subscriptions = $user->getSubscriptions();
        $subscribers = $user->getSubscribers();

        return $this->render('user/network.html.twig', [
            'subscriptions' => $subscriptions,
            'subscribers' => $subscribers,
        ]);
    }


}
