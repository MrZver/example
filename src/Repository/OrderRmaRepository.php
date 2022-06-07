<?php

namespace Boodmo\Sales\Repository;

use Boodmo\Catalog\Repository\ActionsEntityAwareTrait;
use Boodmo\Catalog\Repository\DoctrineHydratorAwareInterface;
use Boodmo\Catalog\Repository\DoctrineHydratorAwareTrait;
use Boodmo\Sales\Entity\OrderRma;
use BoodmoApi\Model\ApiFilter\ApiFilter;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;

class OrderRmaRepository extends EntityRepository implements DoctrineHydratorAwareInterface
{
    use DoctrineHydratorAwareTrait, ActionsEntityAwareTrait;

    public function getClassName()
    {
        return OrderRma::class;
    }

    /**
     * @param ApiFilter $apiFilter
     * @param string $status
     * @param int $pageSize
     * @param int $currentPage
     *
     * @return Paginator
     */
    public function getReturnsList(
        ApiFilter $apiFilter,
        string $status = OrderRma::STATUS_REQUESTED,
        $pageSize = 100,
        $currentPage = 1
    ) {
        $dql = "SELECT 
                  r, i, p, b, cp
                FROM Boodmo\Sales\Entity\OrderRma r
                    LEFT JOIN r.orderItem i
                    LEFT JOIN i.package p
                    LEFT JOIN p.bundle b
                    LEFT JOIN b.customerProfile cp
                WHERE r.status = '" . $status . "'
        ";

        $filterDql = $apiFilter->setQueryConfig($dql)
            ->applyFilter('', false);

        $query = $this->getEntityManager()->createQuery($filterDql)
            ->setFirstResult($pageSize * ($currentPage - 1))
            ->setMaxResults($pageSize)
            ->setHydrationMode(Query::HYDRATE_ARRAY);

        return new Paginator($query);
    }

    public function getCountByStatus($status)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('rma'))
        ->from('Boodmo\Sales\Entity\OrderRma', 'rma')
        ->where('rma.status = ?1')
        ->setParameter(1, $status);
        $query = $qb->getQuery();
        $count = $query->getSingleScalarResult();

        return $count;
    }

    public function findByCustomer(int $customerId): array
    {
        $dql = '
            SELECT 
                  r
                FROM Boodmo\Sales\Entity\OrderRma r
                    LEFT JOIN r.orderItem i
                    LEFT JOIN i.package p
                    LEFT JOIN p.bundle b
                WHERE b.customerProfile = :customerProfile
                ORDER BY r.createdAt DESC
        ';
        return $this->getEntityManager()->createQuery($dql)->setParameter('customerProfile', $customerId)->getResult();
    }

    public function isCustomerHasRma(int $customerId): bool
    {
        $dql = '
            SELECT 
                  COUNT(r)
                FROM Boodmo\Sales\Entity\OrderRma r
                    LEFT JOIN r.orderItem i
                    LEFT JOIN i.package p
                    LEFT JOIN p.bundle b
                WHERE b.customerProfile = :customerProfile
        ';
        return (bool)$this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('customerProfile', $customerId)
            ->getSingleScalarResult();
    }
}
