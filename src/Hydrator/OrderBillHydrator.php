<?php

namespace Boodmo\Sales\Hydrator;

use Boodmo\Core\Hydrator\BaseHydrator;
use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Entity\OrderCreditPointApplied;

class OrderBillHydrator extends BaseHydrator
{
    /** @var OrderPaymentsAppliedHydrator */
    private $paymentsAppliedHydrator;

    /** @var OrderCreditPointAppliedHydrator */
    private $creditPointsAppliedHydrator;

    public function __construct()
    {
        parent::__construct();
        $this->paymentsAppliedHydrator = new OrderPaymentsAppliedHydrator();
        $this->creditPointsAppliedHydrator = new OrderCreditPointAppliedHydrator();
    }

    /**
     * @param OrderBill $entity
     * @return array
     */
    public function extract($entity): array
    {
        foreach ($entity->getPaymentsApplied() as $applied) {
            $paymentsApplied[] = $this->paymentsAppliedHydrator->extract($applied);
        }
        foreach ($entity->getCreditPointsApplied() as $applied) {
            $creditPointsApplied[] = $this->creditPointsAppliedHydrator->extract($applied);
        }
        $custom = [
            'payments_applied' => $paymentsApplied ?? [],
            'credit_points_applied' => $creditPointsApplied ?? []
        ];

        return array_merge($this->classMethods->extract($entity), $custom);
    }
}
