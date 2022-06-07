<?php
/**
 * Created by PhpStorm.
 * User: bopop
 * Date: 10/18/16
 * Time: 13:27.
 */

namespace Boodmo\Sales\Model\Payment\Provider;

use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Model\Payment\PaymentModelInterface;
use Zend\Http\Request;

class CashProvider extends AbstractPaymentProvider
{
    const CODE = 'cash';
    protected $name = 'Cash';

    protected $zohoPaymentAccount = '458850000000000361';

    public function authorize(PaymentModelInterface $paymentService, OrderBill $orderBill): array
    {
        parent::authorize($paymentService, $orderBill);

        return [];
    }

    public function capture(PaymentModelInterface $paymentService, Request $request): void
    {
        return;
    }
}
