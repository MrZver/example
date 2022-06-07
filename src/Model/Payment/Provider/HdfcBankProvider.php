<?php

namespace Boodmo\Sales\Model\Payment\Provider;

use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Model\Payment\PaymentModelInterface;
use Zend\Http\Request;

class HdfcBankProvider extends AbstractPaymentProvider
{
    const CODE = 'hdfc_bank';
    protected $name = 'HDFC Bank';
    protected $prepaid = true;
//    protected $zohoPaymentContact = '458850000000158019';
    protected $zohoPaymentAccount = '458850000000133941';

    public function authorize(PaymentModelInterface $paymentService, OrderBill $orderBill): array
    {
        parent::authorize($paymentService, $orderBill);

        return [];
    }

    public function capture(PaymentModelInterface $payment, Request $request): void
    {
        return;
    }
}
