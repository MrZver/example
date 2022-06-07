<?php

namespace Boodmo\Sales\Model\Workflow\Payment\Handler;

use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Entity\CreditPoint;
use Boodmo\Sales\Model\Workflow\Payment\Command\AddCreditPointsCommand;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Sales\Service\FinanceService;
use Boodmo\Sales\Service\PaymentService;
use Boodmo\User\Entity\UserProfile\Customer;
use Boodmo\User\Service\CustomerService;
use Money\Currency;
use Money\Money;

class AddCreditPointsHandler
{
    /** @var MoneyService */
    private $moneyService;

    /** @var FinanceService */
    private $financeService;

    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var CustomerService
     */
    private $customerService;

    /**
     * @var PaymentService
     */
    private $paymentService;

    /**
     * AddCreditPointsHandler constructor.
     *
     * @param MoneyService $moneyService
     * @param FinanceService $financeService
     * @param OrderService $orderService
     * @param CustomerService $customerService
     * @param PaymentService $paymentService
     */
    public function __construct(
        MoneyService $moneyService,
        FinanceService $financeService,
        OrderService $orderService,
        CustomerService $customerService,
        PaymentService $paymentService
    ) {
        $this->moneyService = $moneyService;
        $this->financeService = $financeService;
        $this->orderService = $orderService;
        $this->customerService = $customerService;
        $this->paymentService = $paymentService;
    }

    /**
     * @param AddCreditPointsCommand $command
     * @throws \RuntimeException|\Exception
     */
    public function __invoke(AddCreditPointsCommand $command): void
    {
        $customer = $this->getCustomer($command->getCustomerId());
        $creditPoint = $this->getCreditPoint(
            $customer,
            $command->getTotal(),
            $command->getCurrency(),
            $command->getType(),
            $command->getZohobooksId()
        );

        if (empty($command->getZohobooksId())) {
            $this->financeService->onCreditPointCreate(
                $creditPoint,
                $this->orderService->loadOrderBundle($command->getBundleId())
            );
        }
        $this->paymentService->saveCreditPoint($creditPoint);
    }

    private function getCustomer(int $id): Customer
    {
        $customer = $this->customerService->getRepository()->find($id);
        if ($customer === null) {
            throw new \RuntimeException(sprintf('Customer not found (id: %s)', $id));
        }
        return $customer;
    }

    private function getCreditPoint(
        Customer $customer,
        float $total,
        string $currency,
        string $type,
        string $zohoBooksId
    ): CreditPoint {
        $baseTotal = $this->moneyService->convert(
            $this->moneyService->getMoney($total / 100, $currency),
            new Currency(MoneyService::BASE_CURRENCY),
            MoneyService::BASE_CURRENCY === $currency ? null : Money::ROUND_UP
        )->getAmount();

        return (new CreditPoint())
            ->setCurrency($currency)
            ->setType($type)
            ->setTotal($total)
            ->setBaseTotal($baseTotal)
            ->setCustomerProfile($customer)
            ->setZohoBooksId($zohoBooksId);
    }
}
