<?php

namespace Boodmo\Sales\Model\Workflow\Payment\Handler;

use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Entity\Payment;
use Boodmo\Sales\Model\Workflow\Payment\Command\AddPaymentCommand;
use Boodmo\Sales\Service\FinanceService;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Sales\Service\PaymentService;
use Boodmo\User\Service\CustomerService;
use Doctrine\ORM\EntityManager;
use Money\Currency;
use Money\Money;
use Ramsey\Uuid\Uuid;

class AddPaymentHandler
{
    /** @var MoneyService */
    private $moneyService;

    /**
     * @var PaymentService
     */
    private $paymentService;

    /**
     * @var FinanceService
     */
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
     * @var EntityManager
     */
    private $entityManager;

    /**
     * AddPaymentHandler constructor.
     *
     * @param MoneyService      $moneyService
     * @param PaymentService    $paymentService
     * @param FinanceService    $financeService
     * @param OrderService      $orderService
     * @param CustomerService   $customerService
     * @param EntityManager     $entityManager
     */
    public function __construct(
        MoneyService $moneyService,
        PaymentService $paymentService,
        FinanceService $financeService,
        OrderService $orderService,
        CustomerService $customerService,
        EntityManager $entityManager
    ) {
        $this->moneyService = $moneyService;
        $this->paymentService = $paymentService;
        $this->financeService = $financeService;
        $this->orderService = $orderService;
        $this->customerService = $customerService;
        $this->entityManager = $entityManager;
    }

    /**
     * @param AddPaymentCommand $command
     * @throws \RuntimeException|\InvalidArgumentException|\Exception
     */
    public function __invoke(AddPaymentCommand $command): void
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $customer = $this->customerService->getRepository()->find($command->getCustomerId());
            if ($customer === null) {
                throw new \RuntimeException(sprintf('Customer not found (id: %s)', $command->getCustomerId()));
            }
            $paymentProvider = $this->paymentService->getProviderByCode($command->getMethod());
            $baseTotal = $this->moneyService->convert(
                $this->moneyService->getMoney($command->getTotal() / 100, $command->getCurrency()),
                new Currency(MoneyService::BASE_CURRENCY),
                MoneyService::BASE_CURRENCY === $command->getCurrency() ? null : Money::ROUND_UP
            )->getAmount();

            $payment = (new Payment())
                ->setId($command->getCustomId() ?? (string) Uuid::uuid4())
                ->setPaymentMethod($paymentProvider->getCode())
                ->setCurrency($command->getCurrency())
                ->setTransactionId($command->getTransactionId())
                ->setZohoBooksId($command->getZohobooksId())
                ->setBaseTotal($baseTotal)
                ->setTotal($command->getTotal())
                ->setUpdatedAt(new \DateTime())
                ->setCustomerProfile($customer);

            if (empty($command->getZohobooksId())) {
                $this->financeService->createCustomerPayment($payment, $command->getCashGateway());
            }

            $this->paymentService->save($payment);

            $this->entityManager->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->entityManager->getConnection()->rollBack();
            throw new \Exception(sprintf('Internal Server Error: %s', $e->getMessage()), $e->getCode(), $e);
        }
    }
}
