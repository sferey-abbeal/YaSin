<?php

namespace App\Repository;

use App\Entity\Activity;
use App\Entity\ActivityUser;
use App\Entity\Posts;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Posts|null find($id, $lockMode = null, $lockVersion = null)
 * @method Posts|null findOneBy(array $criteria, array $orderBy = null)
 * @method Posts[]    findAll()
 * @method Posts[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostsRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Posts::class);
    }

    /**
     * @param Posts $posts
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Posts $posts): void
    {
        $em = $this->getEntityManager();
        $em->persist($posts);
        $em->flush();
    }

    public function getPostsForActivity(Activity $activity): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('posts');
        $queryBuilder
            ->select('posts')
            ->where('posts.activity = :activity')
            ->setParameter('activity', $activity);

        return $queryBuilder;
    }

    public function getPostsForUser(User $user): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('posts');
        $queryBuilder
            ->select('posts')
            ->join('posts.activity', 'activity')
            ->leftJoin('activity.activityUsers', 'activity_user')
            ->where(
                $queryBuilder->expr()->andX(
                    'activity_user.user = :user',
                    'activity_user.type = :type'
                )
            )
            ->setParameter('user', $user)
            ->setParameter('type', ActivityUser::TYPE_ASSIGNED);
        return $queryBuilder;
    }
}
