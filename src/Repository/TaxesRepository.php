<?php

namespace Boodmo\Sales\Repository;

use Boodmo\Catalog\Repository\ActionsEntityAwareTrait;
use Boodmo\Catalog\Repository\DoctrineHydratorAwareInterface;
use Boodmo\Catalog\Repository\DoctrineHydratorAwareTrait;
use Doctrine\ORM\EntityRepository;
use Boodmo\Sales\Entity\Taxes;

class TaxesRepository extends EntityRepository implements DoctrineHydratorAwareInterface
{
    use DoctrineHydratorAwareTrait, ActionsEntityAwareTrait;

    public function getClassName()
    {
        return Taxes::class;
    }

    public function findByFamilyId(?int $familyId)
    {
        $qb = $this->createQueryBuilder('t');

        if (is_null($familyId)) {
            $qb->where($qb->expr()->isNull('t.family'));
        } else {
            $qb->where($qb->expr()->eq('t.family', $familyId));
        }

        $query = $qb->getQuery();

        return $query->getArrayResult();
    }
}
