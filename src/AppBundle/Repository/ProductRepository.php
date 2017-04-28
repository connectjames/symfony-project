<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class ProductRepository extends EntityRepository
{
    public function sortProductsFromCategory($categoryName)
    {
        return $this->createQueryBuilder('catProd')
            ->innerJoin('catProd.categories', 'cat')
            ->andWhere('cat.slug = :categoryName')
            ->setParameter('categoryName', $categoryName)
            ->getQuery()
            ->execute();
    }
}