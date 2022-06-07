<?php
/**
 * Created by PhpStorm.
 * User: bopop
 * Date: 10/18/16
 * Time: 13:15
 */

namespace Boodmo\Sales\Model\Payment;

use Boodmo\Sales\Entity\Payment;
use Doctrine\Common\Collections\ArrayCollection;

interface PaymentModelInterface
{
    public function markAsPaid(string $billId, string $transactionId, float $amount = 0, array $history = []): void;
    public function getPayment($id): Payment;
    public function getProviderList(): ArrayCollection;
}
