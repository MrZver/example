<?php

namespace Boodmo\Sales\Model\Payment;

use Boodmo\Sales\Entity\OrderBill;

interface BillModelInterface
{
    public function loadBill(string $id) : OrderBill;
}
