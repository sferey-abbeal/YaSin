<?php

namespace App\Repository;

use App\Entity\Activity;
use App\Entity\Comment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

/**
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * Persist an Activity.
     * @param Comment $comment
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Comment $comment): void
    {
        $em = $this->getEntityManager();
        $em->persist($comment);
        $em->flush();
    }

    public function getCommentsForActivity(Activity $activity): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('comment');
        $queryBuilder
            ->select('comment')
            ->where('comment.activity = :activity')
            ->setParameter('activity', $activity);

        return $queryBuilder;
    }

    /**
     * @param User $user
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function anonymizeUserComments(User $user): void
    {
        $userComments = $this->findBy(array('user' => $user));
        foreach ($userComments as $userComment) {
            $userComment->setUser(null);
        }
        $em = $this->getEntityManager();
        $em->flush();
    }
}
