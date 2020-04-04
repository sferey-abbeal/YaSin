<?php

namespace App\Repository;

use App\Entity\PostsLikes;
use App\Entity\Posts;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method PostsLikes|null find($id, $lockMode = null, $lockVersion = null)
 * @method PostsLikes|null findOneBy(array $criteria, array $orderBy = null)
 * @method PostsLikes[]    findAll()
 * @method PostsLikes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LikeRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, PostsLikes::class);
    }

    /**
     * Create an User.
     * @param PostsLikes $like
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(PostsLikes $like): void
    {
        $em = $this->getEntityManager();
        $em->persist($like);
        $em->flush();
    }

    /**
     * @param PostsLikes $like
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function delete(PostsLikes $like): void
    {
        $em = $this->getEntityManager();
        $em->remove($like);
        $em->flush();
    }

    /**
     * @param Posts $post
     * @param User $user
     * @return PostsLikes|null
     * @throws NonUniqueResultException
     */
    public function findLike(Posts $post, User $user): ?PostsLikes
    {
        $queryBuilder = $this->createQueryBuilder('posts_likes');
        $queryBuilder
            ->select('posts_likes')
            ->where(
                $queryBuilder->expr()->andX(
                    'posts_likes.user = :user',
                    'posts_likes.post = :post'
                )
            )
            ->setMaxResults(1)
            ->setParameter('user', $user)
            ->setParameter('post', $post);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
