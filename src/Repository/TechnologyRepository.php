<?php

namespace App\Repository;

use App\Entity\Technology;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Technology|null find($id, $lockMode = null, $lockVersion = null)
 * @method Technology|null findOneBy(array $criteria, array $orderBy = null)
 * @method Technology[]    findAll()
 * @method Technology[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TechnologyRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Technology::class);
    }

    public function getTechnologies(): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('technology');
        $queryBuilder
            ->select('technology')
            ->orderBy('technology.name', 'asc');

        return $queryBuilder;
    }
}
