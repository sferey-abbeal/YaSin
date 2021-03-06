<?php

namespace App\Repository;

use App\Entity\Activity;
use App\Entity\ActivityUser;
use App\Entity\User;
use App\Filters\UserListFilter;
use App\Filters\UserListPagination;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    /**
     * @var CommentRepository
     */
    private $commentRepository;
    /**
     * @var FeedbackRepository
     */
    private $feedbackRepository;

    public function __construct(
        RegistryInterface $registry,
        CommentRepository $commentRepository,
        FeedbackRepository $feedbackRepository
    )
    {
        parent::__construct($registry, User::class);
        $this->commentRepository = $commentRepository;
        $this->feedbackRepository = $feedbackRepository;
    }

    /**
     * Create an User.
     * @param User $newUser
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(User $newUser): void
    {
        $em = $this->getEntityManager();
        $em->persist($newUser);
        $em->flush();
    }

    /**
     * @param User $user
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function delete(User $user): void
    {
        $this->commentRepository->anonymizeUserComments($user);
        $this->feedbackRepository->anonymizeUserFeedback($user);
        $em = $this->getEntityManager();
        $em->remove($user);
        $em->flush();
    }

    public function getAssignedUsersForActivity(Activity $activity): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('user');
        $queryBuilder
            ->select('user')
            ->leftJoin('user.activityUsers', 'activityUsers')
            ->where('activityUsers.activity = :activity')
            ->andWhere('activityUsers.type = :type')
            ->setParameter('activity', $activity)
            ->setParameter('type', ActivityUser::TYPE_ASSIGNED);

        return $queryBuilder;
    }

    /**
     * @param UserListFilter $userListFilter
     * @return QueryBuilder
     */
    public function getUserList(
        UserListFilter $userListFilter
    ): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('user');
        $queryBuilder
            ->select('user');
        if (!empty($userListFilter->technology)) {
            $queryBuilder->join('user.technologies', 'technology')
                ->andWhere('technology IN (:technologyFilter)')
                ->setParameter('technologyFilter', $userListFilter->technology);
        }
        return $queryBuilder;
    }

    public function getPaginatedUserList(
        UserListPagination $userListPagination,
        UserListFilter $userListFilter
    ): Query
    {
        $queryBuilder = $this->getUserList($userListFilter);
        if ($userListPagination->pageSize === -1) {
            return $queryBuilder->getQuery();
        }
        $currentPage = $userListPagination->currentPage < 1 ? 1 : $userListPagination->currentPage;
        $firstResult = ($currentPage - 1) * $userListPagination->pageSize;

        $query = $queryBuilder
            ->setFirstResult($firstResult)
            ->setMaxResults($userListPagination->pageSize)
            ->getQuery();

        return $query;
    }

    public function getUsersForActivityListPaginated(
        UserListPagination $userListPagination,
        UserListFilter $userListFilter,
        Activity $activity
    ): Query
    {
        $queryBuilder = $this->getUsersForActivity($userListFilter, $activity);
        if ($userListPagination->pageSize === -1) {
            return $queryBuilder->getQuery();
        }
        $currentPage = $userListPagination->currentPage < 1 ? 1 : $userListPagination->currentPage;
        $firstResult = ($currentPage - 1) * $userListPagination->pageSize;

        $query = $queryBuilder
            ->setFirstResult($firstResult)
            ->setMaxResults($userListPagination->pageSize)
            ->getQuery();

        return $query;
    }

    public function getUsersForActivity(
        UserListFilter $userListFilter,
        Activity $activity
    ): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('user');
        $queryBuilder
            ->select('DISTINCT user')
            ->join('user.activityUsers', 'activityUsers')
            ->where('activityUsers.activity = :activity')
            ->setParameter('activity', $activity);
        if ($userListFilter->activityRole !== null) {
            $queryBuilder
                ->andWhere('activityUsers.type IN (:activityRoleFilter)')
                ->setParameter('activityRoleFilter', $userListFilter->activityRole);
        }
        return $queryBuilder;
    }
}
