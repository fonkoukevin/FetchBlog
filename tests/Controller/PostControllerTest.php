<?php

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Post;
use App\Entity\Status;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PostControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private PostRepository $postRepository;
    private CategoryRepository $categoryRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        $this->postRepository = $this->entityManager->getRepository(Post::class);
        $this->categoryRepository = $this->entityManager->getRepository(Category::class);
    }

    public function testFilterPostsByCategory()
    {
        // Créer un client pour démarrer le kernel
        $client = static::createClient();

        // Récupérer les services nécessaires
        $container = $client->getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        // Démarrer une transaction pour éviter de polluer la base de données de test
        $entityManager->beginTransaction();

        try {
            // Créer une catégorie de test
            $category = new Category();
            $category->setName('Test Category');
            $category->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($category);

            // Créer un utilisateur de test
            $user = new User();
            $user->setUsername('testuser');
            $user->setPassword($passwordHasher->hashPassword($user, 'password'));
            $user->setEmail('testuser@example.com');
            $entityManager->persist($user);

            // Flusher ici pour générer les IDs de l'utilisateur et de la catégorie
            $entityManager->flush();

            // Créer des posts de test
            $post1 = new Post();
            $post1->setTitle('Post 1');
            $post1->setSlug('post-1');
            $post1->setContent('Content of post 1');
            $post1->setCreatedAt(new \DateTimeImmutable());
            $post1->setUpdatedAt(new \DateTimeImmutable());
            $post1->setUser($user);
            $post1->addCategory($category);
            $entityManager->persist($post1);

            $post2 = new Post();
            $post2->setTitle('Post 2');
            $post2->setSlug('post-2');
            $post2->setContent('Content of post 2');
            $post2->setCreatedAt(new \DateTimeImmutable());
            $post2->setUpdatedAt(new \DateTimeImmutable());
            $post2->setUser($user);
            $post2->addCategory($category);
            $entityManager->persist($post2);

            $entityManager->flush();

            // Faire une requête GET à la route de filtrage
            $client->request('GET', '/post/filter', ['category_id' => $category->getId()]);

            $response = $client->getResponse();
            $this->assertTrue($response->isSuccessful());
            $this->assertStringContainsString('Post 1', $response->getContent());
            $this->assertStringContainsString('Post 2', $response->getContent());
        } finally {
            // Revenir en arrière pour éviter de polluer la base de données de test
            $entityManager->rollback();
        }
    }
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
