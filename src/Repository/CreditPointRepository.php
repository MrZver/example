<?php

namespace Boodmo\Sales\Repository;

use Boodmo\Catalog\Repository\ActionsEntityAwareTrait;
use Boodmo\Catalog\Repository\DoctrineHydratorAwareInterface;
use Boodmo\Catalog\Repository\DoctrineHydratorAwareTrait;
use Boodmo\Sales\Entity\CreditPoint;
use Doctrine\ORM\EntityRepository;

class CreditPointRepository extends EntityRepository implements DoctrineHydratorAwareInterface
{
    use DoctrineHydratorAwareTrait, ActionsEntityAwareTrait;

    public function getClassName()
    {
        return CreditPoint::class;
    }

    public function getCustomerFreeCreditPoints(int $customerId)
    {
        $qb = $this->createQueryBuilder('cp');

        return $qb->select('cp')
            ->leftJoin('cp.creditPointsApplied', 'cpa')
            ->where('cp.customerProfile = :customerId')
            ->groupBy('cp.id', 'cpa.amount')
            ->having('sum(cpa.amount) < cp.total OR cpa.amount is NULL')
            ->setParameter('customerId', $customerId)
            ->getQuery()
            ->getResult();
    }
}
