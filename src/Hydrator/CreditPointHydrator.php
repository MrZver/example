<?php


namespace Boodmo\Sales\Hydrator;

use Boodmo\Core\Hydrator\BaseHydrator;
use Boodmo\Sales\Entity\CreditPoint;

class CreditPointHydrator extends BaseHydrator
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param CreditPoint $entity
     * @return array
     */
    public function extract($entity): array
    {
        return $this->classMethods->extract($entity);
    }
}
