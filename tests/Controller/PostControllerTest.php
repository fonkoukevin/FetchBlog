<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Post;

class PostControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
    }

    public function testEditPost()
    {
// Trouver un post existant pour l'éditer (Assurez-vous que 'Post 1' existe dans la base de données de test)
        $post = $this->entityManager->getRepository(Post::class)->findOneBy(['title' => 'Post 1']);
        $this->assertNotNull($post, 'Post not found for editing.');

// Effectuer une requête GET pour afficher le formulaire d'édition du post depuis le dashboard
        $crawler = $this->client->request('GET', '/dashboard/edit/' . $post->getId());
        $this->assertResponseIsSuccessful('Failed to load edit form.');

// Sélectionner le formulaire en utilisant le texte 'soumettre' du bouton
        $form = $crawler->selectButton('soumettre')->form([
            'post[title]' => 'Updated Title',
            'post[content]' => 'Updated content for testing.'
        ]);

// Envoyer la requête POST avec les données modifiées
        $this->client->submit($form);

// Suivre la redirection
        $this->client->followRedirect();

// Recharger le post depuis la base de données pour vérifier les modifications
        $this->entityManager->refresh($post);
        $updatedPost = $this->entityManager->getRepository(Post::class)->find($post->getId());

// Vérifier que le titre et le contenu du post ont été mis à jour
        $this->assertEquals('Updated Title', $updatedPost->getTitle(), 'Post title was not updated.');
        $this->assertEquals('Updated content for testing.', $updatedPost->getContent(), 'Post content was not updated.');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
