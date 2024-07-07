<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function findByTitleOrUsername(string $query): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->andWhere('p.title LIKE :query OR u.username LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('p.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findTopUsersByLikes(int $limit = 3): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('u AS user, COUNT(l.id) AS likeCount')
            ->join('p.likes', 'l')
            ->join('p.user', 'u')
            ->groupBy('u.id')
            ->orderBy('likeCount', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

// src/Repository/PostRepository.php

    public function findByCategory($categoryId)
    {
        return $this->createQueryBuilder('p')
            ->join('p.categories', 'c')
            ->where('c.id = :categoryId')
            ->setParameter('categoryId', $categoryId)
            ->getQuery()
            ->getResult();
    }


    public function findByStatus(int $statusId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :statusId')
            ->setParameter('statusId', $statusId)
            ->orderBy('p.createdAt', 'ASC') // Vous pouvez personnaliser l'ordre
            ->getQuery()
            ->getResult();
    }
    //    /**
    //     * @return Post[] Returns an array of Post objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Post
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
