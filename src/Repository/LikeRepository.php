<?php

namespace App\Repository;

use App\Entity\Like;
use App\Entity\Posts;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Like|null find($id, $lockMode = null, $lockVersion = null)
 * @method Like|null findOneBy(array $criteria, array $orderBy = null)
 * @method Like[]    findAll()
 * @method Like[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LikeRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Like::class);
    }

    /**
     * Create an User.
     * @param Like $like
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Like $like): void
    {
        $em = $this->getEntityManager();
        $em->persist($like);
        $em->flush();
    }

    /**
     * @param Like $like
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function delete(Like $like): void
    {
        $em = $this->getEntityManager();
        $em->remove($like);
        $em->flush();
    }

    /**
     * @param Posts $post
     * @param User $user
     * @return Like|null
     * @throws NonUniqueResultException
     */
    public function findLike(Posts $post, User $user): ?Like
    {
        $queryBuilder = $this->createQueryBuilder('like');
        $queryBuilder
            ->select('like')
            ->where('like.post = :post')
            ->andWhere('like.user = :user')
            ->setParameter('post', $post)
            ->setParameter('user', $user);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
