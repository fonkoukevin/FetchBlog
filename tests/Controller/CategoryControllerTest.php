<?php

namespace App\Tests\Controller;

use App\Entity\Category;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;

class CategoryControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $categoryId;

//    protected function setUp(): void
//    {
//        $this->client = static::createClient();
//        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
//    }

//    public function testCreateNewCategory(): void
//    {
//        // Request the category creation page
//        $crawler = $this->client->request('GET', '/category');
//
//        // Fill in the form
//        $form = $crawler->selectButton('Create')->form([
//            'category[name]' => 'Test Category',
//        ]);
//
//        // Submit the form
//        $this->client->submit($form);
//
//        // Check for a redirect response
//        $this->assertTrue($this->client->getResponse()->isRedirect('/category'));
//
//        // Follow the redirect
//        $this->client->followRedirect();
//
//        // Verify the new category is in the database
//        $category = $this->entityManager->getRepository(Category::class)->findOneBy(['name' => 'Test Category']);
//        $this->assertNotNull($category);
//        $this->assertEquals('Test Category', $category->getName());
//    }

    protected function setUp(): void
    {$this->categoryId = 1; // Par exemple
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();

        // Create a category in the database
        $category = new Category();
        $category->setName('Category to edit');
        $category->setCreatedAt(new \DateTimeImmutable());
        $category->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        // Store the ID of the created category for later use in tests
        $this->categoryId = $category->getId();
    }

    public function testEditCategory(): void
    {
        // Retrieve the category from the database using its ID
        $category = $this->entityManager->getRepository(Category::class)->find($this->categoryId);

        // Request the edit category page
        $crawler = $this->client->request('GET', '/category/edit/' . $category->getId());

        // Assert that the response is successful
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // Fill in the form with new data
        $form = $crawler->selectButton('Save changes')->form([
            'category[name]' => 'Edited Category Name',
        ]);

        // Submit the form
        $this->client->submit($form);

        // Check for a redirect response
        $this->assertTrue($this->client->getResponse()->isRedirect('/category'));

        // Follow the redirect
        $this->client->followRedirect();

        // Verify that the edited category exists in the database
        $editedCategory = $this->entityManager->getRepository(Category::class)->find($this->categoryId);
        $this->assertEquals('Edited Category Name', $editedCategory->getName());
    }
    protected function tearDown(): void
    {
        parent::tearDown();

        // Remove the category from the database
        $category = $this->entityManager->getRepository(Category::class)->find($this->categoryId);
        if ($category) {
            $this->entityManager->remove($category);
            $this->entityManager->flush();
        }

        $this->entityManager->close();
        $this->entityManager = null;
    }

//    protected function tearDown(): void
//    {
//        parent::tearDown();
//        $this->entityManager->close();
//        $this->entityManager = null;
//    }
}
