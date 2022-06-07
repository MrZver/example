<?php

namespace Boodmo\Sales\Hydrator;

use Boodmo\Core\Hydrator\BaseHydrator;
use Boodmo\Sales\Entity\OrderBid;
use Boodmo\User\Hydrator\SupplierHydrator;

class OrderBidHydrator extends BaseHydrator
{
    /** @var  SupplierHydrator */
    private $supplierHydrator;

    public function __construct()
    {
        parent::__construct();
        $this->supplierHydrator = new SupplierHydrator();
    }

    /**
     * @param OrderBid $entity
     * @return array
     */
    public function extract($entity): array
    {
        $supplierProfile = $entity->getSupplierProfile() ?
            $this->supplierHydrator->extract($entity->getSupplierProfile()) : [];

        return array_merge(
            $this->classMethods->extract($entity),
            ['supplier_profile' => $supplierProfile]
        );
    }
}
