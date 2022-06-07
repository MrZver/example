<?php

namespace Boodmo\Sales\Hydrator;

use Boodmo\Core\Hydrator\BaseHydrator;
use Boodmo\Sales\Entity\CreditMemo;

class CreditMemoHydrator extends BaseHydrator
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param CreditMemo $entity
     * @return array
     */
    public function extract($entity): array
    {
        return $this->classMethods->extract($entity);
    }
}
