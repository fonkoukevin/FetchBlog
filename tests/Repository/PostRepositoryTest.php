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
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function testFindByCategory()
    {
        // Create User
        $user = new User();
        $user->setUsername('testuser');
        $user->setPassword('testpassword'); // Set the password
        $user->setEmail('testuser@example.com'); // Set the email
        // Set other User properties as needed
        $this->entityManager->persist($user);

        // Create Status
        $status = new Status();
        $status->setName('published');
        // Set other Status properties as needed
        $this->entityManager->persist($status);

        // Flush to get IDs assigned
        $this->entityManager->flush();

        // Create Post
        $post = new Post();
        $post->setTitle('Sample Title');
        $post->setSlug('sample-title');
        $post->setContent('This is a sample content.');
        $post->setCreatedAt(new \DateTimeImmutable());
        $post->setUpdatedAt(new \DateTimeImmutable());
        $post->setUser($user);
        $post->setStatus($status);
        // Persist and flush Post
        $this->entityManager->persist($post);
        $this->entityManager->flush();

        // Assuming you have a method findByCategory in your repository
        $postRepository = $this->entityManager->getRepository(Post::class);
        $result = $postRepository->findByCategory($status);

        $this->assertNotEmpty($result);
        $this->assertInstanceOf(Post::class, $result[0]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null; // avoid memory leaks
    }
}
