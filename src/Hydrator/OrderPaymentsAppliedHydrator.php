<?php

namespace Boodmo\Sales\Hydrator;

use Boodmo\Core\Hydrator\BaseHydrator;
use Boodmo\Sales\Entity\OrderPaymentApplied;

class OrderPaymentsAppliedHydrator extends BaseHydrator
{
    /** @var PaymentHydrator */
    private $paymentHydrator;

    public function __construct()
    {
        parent::__construct();
        $this->paymentHydrator = new PaymentHydrator();
    }

    /**
     * @param OrderPaymentApplied $entity
     * @return array
     */
    public function extract($entity): array
    {
        $custom = [
            'payment' => $this->paymentHydrator->extract($entity->getPayment())
        ];

        return array_merge($this->classMethods->extract($entity), $custom);
    }
}
