<?php

namespace Boodmo\Sales\Model\Workflow\Payment\Handler;

use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Model\Workflow\Payment\Command\AddBillCommand;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Sales\Service\PaymentService;
use Money\Currency;
use Money\Money;

class AddBillHandler
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

    public function __invoke(AddBillCommand $command): void
    {
        $bundle    = $this->orderService->loadOrderBundle($command->getBundleId());
        $baseTotal = $this->moneyService->convert(
            $this->moneyService->getMoney($command->getTotal() / 100, $command->getCurrency()),
            new Currency(MoneyService::BASE_CURRENCY),
            MoneyService::BASE_CURRENCY === $command->getCurrency() ? null : Money::ROUND_UP
        )->getAmount();

        $orderBill = (new OrderBill())
            ->setType($command->getType())
            ->setCurrency($command->getCurrency())
            ->setBaseTotal($baseTotal)
            ->setTotal($command->getTotal())
            ->setPaymentMethod($command->getMethod());

        $bundle->addBill($orderBill);

        $this->orderService->save($bundle);
    }
}
