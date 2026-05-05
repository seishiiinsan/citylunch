<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return Product[]
     */
    public function findAvailableToday(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.availableDate = :today')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->orderBy('p.type', 'ASC')
            ->addOrderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
