<?php

namespace Boodmo\Sales\Hydrator;

use Boodmo\Core\Hydrator\BaseHydrator;
use Boodmo\Sales\Entity\OrderRma;

class OrderRmaHydrator extends BaseHydrator
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param OrderRma $entity
     * @return array
     */
    public function extract($entity): array
    {
        $orderItem = $entity->getOrderItem();
        $custom    = [
            'order_item' => [
                'name'   => $orderItem->getName(),
                'brand'  => $orderItem->getBrand(),
                'number' => $orderItem->getNumber(),
            ]
        ];
        return array_merge($this->classMethods->extract($entity), $custom);
    }
}
