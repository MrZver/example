<?php

namespace Boodmo\Sales\Model\Workflow\Payment\Handler;

use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Model\Workflow\Payment\Command\EditPaymentCommand;
use Boodmo\Sales\Service\PaymentService;
use Money\Currency;
use Money\Money;

class EditPaymentHandler
{
    /**
     * @var PaymentService
     */
    private $paymentService;

    /**
     * @var MoneyService
     */
    private $moneyService;

    /**
     * EditPaymentHandler constructor.
     *
     * @param PaymentService    $paymentService
     * @param MoneyService      $moneyService
     * @throws \RuntimeException
     */
    public function __construct(PaymentService $paymentService, MoneyService $moneyService)
    {
        $this->paymentService = $paymentService;
        $this->moneyService = $moneyService;
    }

    public function __invoke(EditPaymentCommand $command): void
    {
        $payment = $this->paymentService->getPayment($command->getPaymentId());

        if ($command->getTotal() < $payment->getUsedAmount()) {
            throw new \InvalidArgumentException(
                sprintf(
                    'New total (%s) could not be less of used amount (%s) (payment id: %s).',
                    $command->getTotal(),
                    $payment->getUsedAmount(),
                    $payment->getId()
                )
            );
        }
        $baseTotal = $this->moneyService->convert(
            $this->moneyService->getMoney($command->getTotal() / 100, $payment->getCurrency()),
            new Currency(MoneyService::BASE_CURRENCY),
            MoneyService::BASE_CURRENCY === $payment->getCurrency() ? null : Money::ROUND_UP
        )->getAmount();

        $payment->setTransactionId($command->getTransactionId())
            ->setPaymentMethod($command->getMethod())
            ->setTotal($command->getTotal())
            ->setBaseTotal($baseTotal);
        if (!empty($command->getZohobooksId()) && $command->getZohobooksId() !== $payment->getZohoBooksId()) {
            $payment->setZohoBooksId($command->getZohobooksId());
        }

        $this->paymentService->save($payment);
    }
}
