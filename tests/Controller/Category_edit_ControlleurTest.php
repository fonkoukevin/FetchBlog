<?php


namespace App\Tests\Controller;

use App\Entity\Category;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;

class Category_edit_ControlleurTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $categoryId;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();

        // Créer une catégorie dans la base de données
        $category = new Category();
        $category->setName('Category to edit');
        $category->setCreatedAt(new \DateTimeImmutable());
        $category->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        // Stocker l'ID de la catégorie créée pour une utilisation ultérieure dans les tests
        $this->categoryId = $category->getId();
    }

    public function testEditCategory(): void
    {
        // Récupérer la catégorie de la base de données en utilisant son ID
        $category = $this->entityManager->getRepository(Category::class)->find($this->categoryId);
        $this->assertNotNull($category, 'La catégorie à éditer n\'existe pas.');

        // Faire une requête GET pour afficher la page d'édition de la catégorie
        $crawler = $this->client->request('GET', '/category/edit/' . $category->getId());

        // Vérifier que la réponse est réussie
        $this->assertResponseIsSuccessful('Échec du chargement du formulaire d\'édition.');

        // Remplir le formulaire avec de nouvelles données
        $form = $crawler->selectButton('soumettre')->form([
            'category[name]' => 'Edited Category Name',
        ]);
        // Soumettre le formulaire
        $this->client->submit($form);

        // Suivre la redirection après soumission du formulaire
        $this->client->followRedirect();

        // Vérifier que la catégorie modifiée existe dans la base de données
        $editedCategory = $this->entityManager->getRepository(Category::class)->find($this->categoryId);
        $this->assertEquals('Edited Category Name', $editedCategory->getName(), 'n\'a pas été mis à jour.');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Supprimer la catégorie de la base de données
        $category = $this->entityManager->getRepository(Category::class)->find($this->categoryId);
        if ($category) {
            $this->entityManager->remove($category);
            $this->entityManager->flush();
        }

        $this->entityManager->close();
        $this->entityManager = null;
    }
}
