<?php

namespace Boodmo\Sales\Model\Workflow\Payment\Handler;

use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Entity\CreditMemo;
use Boodmo\Sales\Model\Workflow\Payment\Command\NewCreditMemoCommand;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Sales\Service\PaymentService;
use Money\Currency;
use Money\Money;

class NewCreditMemoHandler
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

    public function __invoke(NewCreditMemoCommand $command): void
    {
        $bundle    = $this->orderService->loadOrderBundle($command->getBundleId());
        $baseTotal = $this->moneyService->convert(
            $this->moneyService->getMoney($command->getTotal() / 100, $command->getCurrency()),
            new Currency(MoneyService::BASE_CURRENCY),
            MoneyService::BASE_CURRENCY === $command->getCurrency() ? null : Money::ROUND_UP
        )->getAmount();

        $creditMemo = (new CreditMemo())
            ->setTotal($command->getTotal())
            ->setCurrency($command->getCurrency())
            ->setOpen($command->getOpen())
            ->setBaseTotal($baseTotal)
            ->setCalculatedTotal($command->getCalculatedTotal());
        $bundle->addCreditMemo($creditMemo);

        $this->orderService->save($bundle);
    }
}
