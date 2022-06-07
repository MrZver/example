<?php

namespace Boodmo\Sales\Model\Workflow\Payment\Handler;

use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Model\Workflow\Payment\Command\EditCreditPointsCommand;
use Boodmo\Sales\Service\PaymentService;
use Money\Currency;
use Money\Money;

class EditCreditPointsHandler
{
    /** @var MoneyService */
    private $moneyService;

    /**
     * @var PaymentService
     */
    private $paymentService;

    /**
     * AddCreditPointsHandler constructor.
     *
     * @param MoneyService $moneyService
     * @param PaymentService $paymentService
     */
    public function __construct(
        MoneyService $moneyService,
        PaymentService $paymentService
    ) {
        $this->moneyService = $moneyService;
        $this->paymentService = $paymentService;
    }

    public function __invoke(EditCreditPointsCommand $command): void
    {
        $creditPoint = $this->paymentService->getCreditPoint($command->getCreditPointId());
        $baseTotal = $this->moneyService->convert(
            $this->moneyService->getMoney($command->getTotal() / 100, $command->getCurrency()),
            new Currency(MoneyService::BASE_CURRENCY),
            MoneyService::BASE_CURRENCY === $command->getCurrency() ? null : Money::ROUND_UP
        )->getAmount();

        $creditPoint = $creditPoint
            ->setCurrency($command->getCurrency())
            ->setType($command->getType())
            ->setTotal($command->getTotal())
            ->setBaseTotal($baseTotal);
        if (!empty($command->getZohobooksId()) && $command->getZohobooksId() !== $creditPoint->getZohoBooksId()) {
            $creditPoint->setZohoBooksId($command->getZohobooksId());
        }

        $this->paymentService->saveCreditPoint($creditPoint);
    }
}
