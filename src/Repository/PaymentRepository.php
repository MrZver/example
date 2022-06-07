<?php

namespace Boodmo\Sales\Repository;

use Boodmo\Catalog\Repository\ActionsEntityAwareTrait;
use Boodmo\Catalog\Repository\DoctrineHydratorAwareInterface;
use Boodmo\Catalog\Repository\DoctrineHydratorAwareTrait;
use Boodmo\Sales\Entity\Payment;
use Doctrine\ORM\EntityRepository;

/**
 * @method null|Payment find($id, $lockMode = \Doctrine\DBAL\LockMode::NONE, $lockVersion = null)
 */
class PaymentRepository extends EntityRepository implements DoctrineHydratorAwareInterface
{
    use DoctrineHydratorAwareTrait, ActionsEntityAwareTrait;

    public function getClassName()
    {
        return Payment::class;
    }

    public function getPayments(array $options = [])
    {
        $qb = $this->createQueryBuilder('op');

        if ($options['limit'] && is_int($options['limit'])) {
            $qb->setMaxResults($options['limit']);
        }

        $query = $qb->getQuery();

        return $query->getResult();
    }

    public function getCustomerFreePayments(int $customerId)
    {
        $qb = $this->createQueryBuilder('p');

        return $qb->select('p')
            ->leftJoin('p.paymentsApplied', 'pa')
            ->where('p.customerProfile = :customerId')
            ->groupBy('p.id', 'pa.amount')
            ->having('sum(pa.amount) < p.total OR pa.amount is NULL')
            ->setParameter('customerId', $customerId)
            ->getQuery()
            ->getResult();
    }
}
