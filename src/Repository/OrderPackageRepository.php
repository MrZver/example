<?php

namespace Boodmo\Sales\Repository;

use Boodmo\Catalog\Repository\ActionsEntityAwareTrait;
use Boodmo\Catalog\Repository\DoctrineHydratorAwareInterface;
use Boodmo\Catalog\Repository\DoctrineHydratorAwareTrait;
use Boodmo\Sales\Model\Workflow\Status\Status;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use BoodmoApi\Model\ApiFilter\ApiFilter;
use Doctrine\ORM\EntityRepository;
use Boodmo\Sales\Entity\OrderPackage;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @method null|OrderPackage find($id, $lockMode = \Doctrine\DBAL\LockMode::NONE, $lockVersion = null)
 */
class OrderPackageRepository extends EntityRepository implements DoctrineHydratorAwareInterface
{
    use DoctrineHydratorAwareTrait, ActionsEntityAwareTrait;

    public function getClassName()
    {
        return OrderPackage::class;
    }

    public function loadSupplierOrderHistoryData(int $supplierId, ApiFilter $apiFilter, $pageSize = 100, $currentPage = 1): Paginator
    {
        $dql = "SELECT 
                  op, i, ob, sb, r
                FROM Boodmo\Sales\Entity\OrderPackage op 
                LEFT JOIN op.bundle ob 
                LEFT JOIN op.items i
                LEFT JOIN op.shippingBox sb
                LEFT JOIN i.rmaList r
                  WHERE op.supplierProfile = " . $supplierId . "
                        AND GET_JSON_FIELD(op.status, '" . Status::TYPE_GENERAL . "') != '" . StatusEnum::PROCESSING . "'
        ";

        $filterDql = $apiFilter->setQueryConfig($dql)
            ->applyFilter('', false);

        $query = $this->getEntityManager()->createQuery($filterDql)
            ->setFirstResult($pageSize * ($currentPage - 1))
            ->setMaxResults($pageSize)
            ->setHydrationMode(Query::HYDRATE_ARRAY);

        return new Paginator($query);
    }
}
