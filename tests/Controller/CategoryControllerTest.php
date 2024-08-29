<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Category;

class CategoryControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
// Créer un client HTTP pour simuler les requêtes web
        $this->client = static::createClient();

// Récupérer l'EntityManager pour les opérations de base de données
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
    }

    public function testCategoryExistsAfterFixtureLoad()
    {
// Récupérer la catégorie créée par les fixtures
        $category = $this->entityManager->getRepository(Category::class)->findOneBy(['name' => 'Test Category']);

// Vérifier que la catégorie existe
        $this->assertNotNull($category);
        $this->assertEquals('Test Category', $category->getName());
    }

    public function testFilterPostsByCategory()
    {
// Effectuer une requête GET pour filtrer les posts par catégorie
        $this->client->request('GET', '/post/filter', ['category_id' => 19]);

// Vérifier que la réponse est réussie
        $this->assertTrue($this->client->getResponse()->isSuccessful());

// Vérifier que le contenu contient les posts de la catégorie filtrée
        $this->assertStringContainsString('Post 1', $this->client->getResponse()->getContent());
        $this->assertStringContainsString('Post 2', $this->client->getResponse()->getContent());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

// Fermer l'EntityManager
        $this->entityManager->close();
        $this->entityManager = null;
    }
}



