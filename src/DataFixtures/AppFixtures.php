<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Post;
use App\Entity\User;
use App\Entity\Status;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
// Création d'un statut
        $status = new Status();
        $status->setName('créer');
        $manager->persist($status);

// Création d'un utilisateur de test
        $user = new User();
        $user->setUsername('testuser');
        $user->setEmail('testuser@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $manager->persist($user);

// Création d'une catégorie
        $category = new Category();
        $category->setName('Test Category');
        $category->setCreatedAt(new \DateTimeImmutable());
        $category->setUpdatedAt(new \DateTimeImmutable());
        $manager->persist($category);

// Création de quelques posts
        $post1 = new Post();
        $post1->setTitle('Post 1');
        $post1->setSlug('post-1');
        $post1->setContent('Content of post 1');
        $post1->setCreatedAt(new \DateTimeImmutable());
        $post1->setUpdatedAt(new \DateTimeImmutable());
        $post1->setUser($user);
        $post1->setStatus($status);
        $post1->addCategory($category);
        $manager->persist($post1);

        $post2 = new Post();
        $post2->setTitle('Post 2');
        $post2->setSlug('post-2');
        $post2->setContent('Content of post 2');
        $post2->setCreatedAt(new \DateTimeImmutable());
        $post2->setUpdatedAt(new \DateTimeImmutable());
        $post2->setUser($user);
        $post2->setStatus($status);
        $post2->addCategory($category);
        $manager->persist($post2);

// Flusher pour persister toutes les entités en base de données
        $manager->flush();
    }
}
