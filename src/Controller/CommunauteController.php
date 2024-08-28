<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

//#[IsGranted('ROLE_ADMIN')]
class CommunauteController extends AbstractController
{
    #[Route('/communaute', name: 'communaute', methods: ['GET'])]
    public function list(EntityManagerInterface $em, NotificationRepository $notificationRepository): Response
    {
        $currentUser = $this->getUser();
        $users = $em->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.id != :currentUserId')
            ->setParameter('currentUserId', $currentUser->getId())
            ->getQuery()
            ->getResult();
        $notifications = $notificationRepository->findAll();
        return $this->render('communaute/index.html.twig',  [
            'users' => $users,
            'notifications' => $notifications,
            'show_navbar' => true,
        ]);
    }
}
