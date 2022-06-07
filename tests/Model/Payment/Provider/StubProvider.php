<?php

namespace Boodmo\SalesTest\Model\Payment\Provider;

use Boodmo\Sales\Model\Payment\PaymentModelInterface;
use Boodmo\Sales\Model\Payment\Provider\AbstractPaymentProvider;
use Zend\Http\Request;

class StubProvider extends AbstractPaymentProvider
{

    protected $name = 'stub';

    public function capture(PaymentModelInterface $payment, Request $request): void
    {
    }
}
