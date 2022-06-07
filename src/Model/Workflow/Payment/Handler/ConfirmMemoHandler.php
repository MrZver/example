<?php

namespace Boodmo\Sales\Model\Workflow\Payment\Handler;

use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Entity\CreditMemo;
use Boodmo\Sales\Model\Workflow\Payment\Command\ConfirmMemoCommand;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Sales\Service\PaymentService;

class ConfirmMemoHandler
{
    /** @var  OrderService */
    private $orderService;

    /** @var  PaymentService */
    private $paymentService;

    /** @var MoneyService */
    private $moneyService;

    public function __construct(
        MoneyService $moneyService,
        PaymentService $paymentService,
        OrderService $orderService
    ) {
        $this->moneyService = $moneyService;
        $this->paymentService = $paymentService;
        $this->orderService = $orderService;
    }

    public function __invoke(ConfirmMemoCommand $command): void
    {
        /* @var CreditMemo $creditMemo */
        $orderBundle = $this->orderService->loadOrderBundle($command->getBundleId());
        $orderBundleNotes = $orderBundle->getNotes();
        $currency = $command->getCurrency();
        $leave = $command->getLeave();

        if ($leave > 0) {
            foreach ($orderBundle->getBills() as $bill) {
                foreach ($bill->getPaymentsApplied() as $paymentApplied) {
                    $payment = $paymentApplied->getPayment();
                    if ($payment->getCurrency() === $currency) {
                        $currentAmount = $paymentApplied->getAmount();
                        if ($currentAmount > $leave) {
                            $paymentApplied->setAmount($currentAmount - $leave);
                            $leave = 0;
                        } else {
                            $leave -= $currentAmount;
                            //Delete payment applied
                            $bill->removePaymentApplied($paymentApplied);
                            $payment->getPaymentsApplied()->removeElement($paymentApplied);
                            $this->paymentService->removePaymentApplied($paymentApplied, false);
                        }
                    }
                    if (empty($leave)) {
                        break 2;
                    }
                }
            }
        }
        $creditMemos = $this->orderService->createCreditMemo($orderBundle);
        //copy notes from order to creditMemo
        if (!empty($orderBundleNotes['CREDITMEMO']) and $creditMemos->count() > 0) {
            foreach ($creditMemos as $creditMemo) {
                $creditMemoNotes = $creditMemo->getNotes();
                $creditMemoNotes['CREDITMEMO'] = array_merge(
                    $orderBundleNotes['CREDITMEMO'],
                    $creditMemoNotes['CREDITMEMO'] ?? []
                );
                $creditMemo->setNotes($creditMemoNotes);
            }
        }
        $this->orderService->save($orderBundle);
    }
}
