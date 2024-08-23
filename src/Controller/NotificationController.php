<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\Subscription;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NotificationController extends AbstractController
{
    #[Route('/notification', name: 'notification')]
    public function notifications(EntityManagerInterface $em,Security $security): Response
    {
        $user = $security->getUser();
//        $user = $this->getUser(); // Récupère l'utilisateur connecté

        // Récupérer les utilisateurs auxquels l'utilisateur actuel est abonné
        $subscriptions = $em->getRepository(Subscription::class)
            ->findBy(['subscriber' => $user]);


        $subscribedUserIds = [];
        foreach ($subscriptions as $subscription) {
            $subscribedUserIds[] = $subscription->getSubscribedTo()->getId();
            dump($subscribedUserIds);
        }

        // Si l'utilisateur n'est abonné à personne, ne récupérer aucune notification
        if (empty($subscribedUserIds)) {
            $notifications = [];
        } else {
            // Récupérer les notifications des utilisateurs auxquels l'utilisateur est abonné,
            // en excluant les notifications de ses propres posts
            $notifications = $em->getRepository(Notification::class)
                ->createQueryBuilder('n')
                ->join('n.post', 'p')
                ->where('p.user IN (:subscribedUserIds)')
                ->andWhere('n.user != :currentUser')  // Exclure les notifications de l'utilisateur actuel
                ->setParameter('subscribedUserIds', $subscribedUserIds)
                ->setParameter('currentUser', $user)
                ->orderBy('n.createdAt', 'DESC')
                ->getQuery()
                ->getResult();
        }

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
            'show_navbar' => True,
        ]);
    }
}
