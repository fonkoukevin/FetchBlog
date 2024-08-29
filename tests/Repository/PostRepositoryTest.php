<?php

namespace App\Tests\Repository;

use App\Entity\Post;
use App\Entity\User;
use App\Entity\Status;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PostRepositoryTest extends KernelTestCase
{
    private $entityManager;

    protected function setUp(): void
    {
        // Démarrage du kernel Symfony pour accéder aux services
        $kernel = self::bootKernel();

        // Récupération de l'EntityManager de Doctrine pour les opérations de base de données
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }
    public function testFindByStatus()
    {
        // Création d'un utilisateur de test
        $user = new User();
        $user->setUsername('testuser11');
        $user->setPassword('testpassword11');
        $user->setEmail('testuser11@example.com');
        $this->entityManager->persist($user);

        // Récupérer le statut existant avec l'ID 1 depuis la base de données
        $status = $this->entityManager->getRepository(Status::class)->find(11);

        // Vérifier que le statut a été trouvé
        $this->assertNotNull($status);
        $this->assertEquals('Creer', $status->getName());

        // Flush pour persister User et générer l'ID
        $this->entityManager->flush();

        // Création d'un post de test associé au statut existant et à l'utilisateur créé
        $post = new Post();
        $post->setTitle('Sample Title');
        $post->setSlug('sample-title');
        $post->setContent('This is a sample content.');
        $post->setCreatedAt(new \DateTimeImmutable());
        $post->setUpdatedAt(new \DateTimeImmutable());
        $post->setUser($user);
        $post->setStatus($status);
        $this->entityManager->persist($post);

        // Flush pour persister le post
        $this->entityManager->flush();

        // Récupération du repository Post pour effectuer la recherche
        $postRepository = $this->entityManager->getRepository(Post::class);

        // Appel de la méthode findByStatus avec l'ID du statut existant
        $result = $postRepository->findByStatus($status->getId());

        // Vérification que le résultat n'est pas vide (au moins un post trouvé)
        $this->assertNotEmpty($result);

        // Vérification que chaque post trouvé a le statut correct
        foreach ($result as $post) {
            $this->assertEquals('Creer', $post->getStatus()->getName());
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Fermeture de l'EntityManager pour libérer les ressources
        $this->entityManager->close();
        $this->entityManager = null; // éviter les fuites de mémoire
    }
}
