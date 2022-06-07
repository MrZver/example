<?php

namespace Boodmo\Sales\Model\Workflow\Payment\Handler;

use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Model\Workflow\Payment\Command\EditBillCommand;
use Boodmo\Sales\Service\PaymentService;
use Money\Currency;
use Money\Money;

class EditBillHandler
{
    /** @var  PaymentService */
    private $paymentService;

    /** @var MoneyService */
    private $moneyService;

    public function __construct(
        MoneyService $moneyService,
        PaymentService $paymentService
    ) {
        $this->moneyService = $moneyService;
        $this->paymentService = $paymentService;
    }

    public function __invoke(EditBillCommand $command): void
    {
        $orderBill = $this->paymentService->loadBill($command->getBillId());
        $baseTotal = $this->moneyService->convert(
            $this->moneyService->getMoney($command->getTotal() / 100, $command->getCurrency()),
            new Currency(MoneyService::BASE_CURRENCY),
            MoneyService::BASE_CURRENCY === $command->getCurrency() ? null : Money::ROUND_UP
        )->getAmount();

        $orderBill->setType($command->getType())
            ->setCurrency($command->getCurrency())
            ->setBaseTotal($baseTotal)
            ->setTotal($command->getTotal())
            ->setPaymentMethod($command->getMethod());

        $this->paymentService->saveOrderBill($orderBill);
    }
}
