<?php

namespace Boodmo\Sales\Repository;

use Boodmo\Catalog\Repository\ActionsEntityAwareTrait;
use Boodmo\Catalog\Repository\DoctrineHydratorAwareInterface;
use Boodmo\Catalog\Repository\DoctrineHydratorAwareTrait;
use Doctrine\ORM\EntityRepository;
use Boodmo\Sales\Entity\OrderItem;

/**
 * @method null|OrderItem find($id, $lockMode = \Doctrine\DBAL\LockMode::NONE, $lockVersion = null)
 */
class OrderItemRepository extends EntityRepository implements DoctrineHydratorAwareInterface
{
    use DoctrineHydratorAwareTrait, ActionsEntityAwareTrait;

    public function getClassName()
    {
        return OrderItem::class;
    }
}
