<?php

namespace App\Repository;

use App\Entity\ActivityType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ActivityType|null find($id, $lockMode = null, $lockVersion = null)
 * @method ActivityType|null findOneBy(array $criteria, array $orderBy = null)
 * @method ActivityType[]    findAll()
 * @method ActivityType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActivityTypeRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ActivityType::class);
    }

    public function getActivityTypes(): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('activity_type');
        $queryBuilder
            ->select('activity_type')
            ->orderBy('activity_type.name');

        return $queryBuilder;
    }
}
