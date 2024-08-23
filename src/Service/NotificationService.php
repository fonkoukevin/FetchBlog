<?php

namespace App\Service;


use App\Entity\Notification;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function createNotification(User $user, Post $post)
    {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setPost($post);
        $notification->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($notification);
        $this->em->flush();
    }
}
