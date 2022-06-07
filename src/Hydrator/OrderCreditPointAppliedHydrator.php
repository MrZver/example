<?php

namespace Boodmo\Sales\Hydrator;

use Boodmo\Core\Hydrator\BaseHydrator;
use Boodmo\Sales\Entity\OrderCreditPointApplied;
use Money\Currency;
use Money\Money;

class OrderCreditPointAppliedHydrator extends BaseHydrator
{
    /** @var CreditPointHydrator */
    private $creditPointHydrator;

    public function __construct()
    {
        parent::__construct();
        $this->creditPointHydrator = new CreditPointHydrator();
    }

    /**
     * @param OrderCreditPointApplied $entity
     * @return array
     */
    public function extract($entity): array
    {
        $custom = [
            'credit_point' => $this->creditPointHydrator->extract($entity->getCreditPoint()),
            'amount_money' => new Money($entity->getAmount(), new Currency($entity->getCreditPoint()->getCurrency()))
        ];

        return array_merge($this->classMethods->extract($entity), $custom);
    }
}
