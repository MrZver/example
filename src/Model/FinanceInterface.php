<?php

namespace Boodmo\Sales\Model;

interface FinanceInterface
{
    public function getZohoPaymentAccount() : string;
}
