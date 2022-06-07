<?php

namespace Boodmo\Sales\Model\Workflow\Payment\Handler;

use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Model\Workflow\Payment\Command\PayToBillCommand;
use Boodmo\Sales\Service\FinanceService;
use Boodmo\Sales\Service\PaymentService;
use Boodmo\Sales\Model\Workflow\Note\NotesMessage;

class PayToBillHandler
{
    /** @var PaymentService */
    private $paymentService;

    /**
     * @var FinanceService
     */
    private $financeService;

    /**
     * PayToBillHandler constructor.
     *
     * @param PaymentService $paymentService
     * @param FinanceService $financeService
     */
    public function __construct(PaymentService $paymentService, FinanceService $financeService)
    {
        $this->paymentService = $paymentService;
        $this->financeService = $financeService;
    }

    /**
     * @param PayToBillCommand $command
     * @throws \RuntimeException
     */
    public function __invoke(PayToBillCommand $command): void
    {
        try {
            $orderBill = $this->paymentService->loadBill($command->getBillId());
            foreach ($command->getPaymentInfo() as [$paymentId, $amount]) {
                $this->processPayment($orderBill, $command->getAppliedId(), $paymentId, $amount);
            }
            foreach ($command->getCreditPointInfo() as [$creditPointId, $amount]) {
                $this->processCreditPoint($orderBill, $command->getAppliedId(), $creditPointId, $amount);
            }
            $this->paymentService->saveOrderBill($orderBill);
        } catch (\Throwable $exception) {
            throw new \RuntimeException(
                sprintf(
                    'Could not process command PayToBill'
                    . '[billId: %s, paymentInfo: %s, creditPointInfo: %s, appliedId: %s]',
                    $command->getBillId(),
                    $command->getPaymentInfo(),
                    $command->getCreditPointInfo(),
                    $command->getAppliedId()
                ),
                $exception
            );
        }
    }

    /**
     * Add|Edit|Delete payment applied
     *
     * @param OrderBill   $orderBill
     * @param null|string $appliedId
     * @param string      $paymentId
     * @param int|null    $amount
     */
    private function processPayment(OrderBill $orderBill, ?string $appliedId, string $paymentId, ?int $amount): void
    {
        if (is_null($appliedId)) {
            $payment = $this->paymentService->getPayment($paymentId);
            $orderBill->addPayment($payment, $amount);

            $noteMessage = sprintf("Payment %s %s (%s) was attached (%s) to bill (%s) order", $payment->getCurrency(), $payment->getTotal()/100, $payment->getPaymentMethod(), $amount/100, $orderBill->getTotal()/100);
            $message = new NotesMessage('SALES', $noteMessage);
            $orderBill->getBundle()->addMessageToNotes($message);
        } else {
            $paymentApplied = $this->paymentService->loadPaymentApplied($appliedId);
            $paymentId = $paymentApplied->getPayment()->getId();
            $payment = $paymentApplied->getPayment();
            if (!empty($amount)) {
                //Edit payment applied
                $orderBill->addPayment($payment, $amount);

                $noteMessage = sprintf("Payment %s %s (%s) was edited attach (%s) to bill (%s) order", $payment->getCurrency(), $payment->getTotal()/100, $payment->getPaymentMethod(), $amount/100, $orderBill->getTotal()/100);
                $message = new NotesMessage('SALES', $noteMessage);
                $orderBill->getBundle()->addMessageToNotes($message);
            } else {
                //Delete payment applied
                $orderBill->removePaymentApplied($paymentApplied);
                $payment->getPaymentsApplied()->removeElement($paymentApplied);
                $this->paymentService->removePaymentApplied($paymentApplied, false);

                $noteMessage = sprintf("Payment %s %s (%s) was detached (%s) from bill (%s) order", $payment->getCurrency(), $payment->getTotal()/100, $payment->getPaymentMethod(), $paymentApplied->getAmount()/100, $orderBill->getTotal()/100);
                $message = new NotesMessage('SALES', $noteMessage);
                $orderBill->getBundle()->addMessageToNotes($message);
            }
        }
        $this->financeService->updateCustomerPaymentReference($payment);
    }

    /**
     * Add|Edit|Delete credit point applied
     *
     * @param OrderBill   $orderBill
     * @param null|string $appliedId
     * @param string      $creditPointId
     * @param int|null    $amount
     */
    private function processCreditPoint(
        OrderBill $orderBill,
        ?string $appliedId,
        string $creditPointId,
        ?int $amount
    ): void {
        if (is_null($appliedId)) {
            $orderBill->addCreditPoint($this->paymentService->getCreditPoint($creditPointId), $amount);
        } else {
            $creditPointApplied = $this->paymentService->loadCreditPointApplied($appliedId);
            $creditPointId = $creditPointApplied->getCreditPoint()->getId();
            if (!empty($amount)) {
                //Edit credit point applied
                $orderBill->addCreditPoint($creditPointApplied->getCreditPoint(), $amount);
            } else {
                //Delete credit point applied
                $orderBill->removeCreditPointApplied($creditPointApplied);
                $this->paymentService->removeCreditPointApplied($creditPointApplied, false);
            }
        }
        $this->financeService->updateCreditNoteReference(
            $this->paymentService->getCreditPoint($creditPointId)
        );
    }
}
