<?php

namespace Boodmo\Sales\Repository;

use Boodmo\Catalog\Repository\ActionsEntityAwareTrait;
use Boodmo\Catalog\Repository\DoctrineHydratorAwareInterface;
use Boodmo\Catalog\Repository\DoctrineHydratorAwareTrait;
use Boodmo\Sales\Entity\OrderCreditPointApplied;
use Doctrine\ORM\EntityRepository;

class OrderCreditPointAppliedRepository extends EntityRepository implements DoctrineHydratorAwareInterface
{
    use DoctrineHydratorAwareTrait, ActionsEntityAwareTrait;

    public function getClassName()
    {
        return OrderCreditPointApplied::class;
    }
}
