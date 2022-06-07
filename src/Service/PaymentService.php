<?php

namespace Boodmo\Sales\Service;

use Boodmo\Email\Service\EmailManager;
use Boodmo\Sales\Entity\CreditPoint;
use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderCreditPointApplied;
use Boodmo\Sales\Entity\OrderPaymentApplied;
use Boodmo\Sales\Entity\Payment;
use Boodmo\Sales\Hydrator\CreditPointHydrator;
use Boodmo\Sales\Hydrator\PaymentHydrator;
use Boodmo\Sales\Model\Event\NotifyEvent;
use Boodmo\Sales\Model\NotifyResult;
use Boodmo\Sales\Model\Payment\PaymentAvailability;
use Boodmo\Sales\Model\Payment\PaymentModelInterface;
use Boodmo\Sales\Model\Payment\PaymentProviderInterface;
use Boodmo\Sales\Model\Payment\Provider\{
    AbstractPaymentProvider, CashProvider, CheckoutProvider, HdfcBankProvider, PayPalProvider, RazorPayProvider, HdfcProvider
};
use Boodmo\Sales\Model\Workflow\Order\Command\ApproveSupplierItemCommand;
use Boodmo\Sales\Model\Workflow\Payment\Command\AddPaymentCommand;
use Boodmo\Sales\Model\Workflow\Payment\Command\PayToBillCommand;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Repository\CreditPointRepository;
use Boodmo\Sales\Repository\OrderBillRepository;
use Boodmo\Sales\Repository\OrderCreditPointAppliedRepository;
use Boodmo\Sales\Repository\OrderPaymentAppliedRepository;
use Boodmo\Sales\Repository\PaymentRepository;
use Boodmo\User\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Zend\Http\Request;
use Ramsey\Uuid\Uuid;
use Prooph\ServiceBus\CommandBus;
use Zend\Log\LoggerInterface;

class PaymentService implements PaymentModelInterface
{
    /** @var $paymentRepository */
    private $paymentRepository;

    /** @var FinanceService */
    private $financeService;

    /** @var OrderService */
    public $orderService;

    /** @var EmailManager */
    public $emailManager;

    /** @var array */
    private $config;

    /** @var array */
    private $providers;

    /** @var CommandBus */
    private $commandBus;

    /** @var OrderBillRepository */
    private $orderBillRepository;

    /** @var CreditPointRepository */
    private $creditPointRepository;

    /** @var PaymentHydrator  */
    private $paymentHydrator;

    /** @var CreditPointHydrator  */
    private $creditPointHydrator;

    /** @var OrderPaymentAppliedRepository */
    private $orderPaymentAppliedRepository;

    /** @var OrderCreditPointAppliedRepository */
    private $orderCreditPointAppliedRepository;

    /* @var LoggerInterface */
    private $logger;

    public function __construct(
        array $providers,
        array $config,
        PaymentRepository $paymentRepository,
        FinanceService $financeService,
        OrderService $orderService,
        EmailManager $emailManager,
        CommandBus $commandBus,
        OrderBillRepository $orderBillRepository,
        CreditPointRepository $creditPointRepository,
        OrderPaymentAppliedRepository $orderPaymentAppliedRepository,
        OrderCreditPointAppliedRepository $orderCreditPointAppliedRepository,
        LoggerInterface $logger
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->financeService = $financeService;
        $this->orderService = $orderService;
        $this->config = $config;
        $this->emailManager = $emailManager;
        $this->providers = $providers;
        $this->commandBus = $commandBus;
        $this->orderBillRepository = $orderBillRepository;
        $this->creditPointRepository = $creditPointRepository;
        $this->orderPaymentAppliedRepository = $orderPaymentAppliedRepository;
        $this->orderCreditPointAppliedRepository = $orderCreditPointAppliedRepository;
        $this->logger = $logger;

        $this->paymentHydrator = new PaymentHydrator();
        $this->creditPointHydrator = new CreditPointHydrator();
    }

    /**
     * Get sorted provider list object
     *
     * @return ArrayCollection|PaymentProviderInterface[]
     */
    public function getProviderList(): ArrayCollection
    {
        /* @var PaymentProviderInterface|AbstractPaymentProvider $provider*/
        $providersList = new ArrayCollection();
        foreach ($this->providers as $class) {
            if (!(class_exists($class) && method_exists($class, 'getCode'))) {
                continue;
            }
            $code = $class::getCode();
            $provider = new $class($this->config[$code] ?? []);
            if ($provider instanceof PaymentProviderInterface) {
                $provider->setLogger($this->logger);
                $providersList->set($code, $provider);
            }
        }
        return $providersList->matching((new Criteria())->orderBy(['sort' => Criteria::ASC]));
    }

    /**
     * @param $billId
     * @return array
     * @throws \RuntimeException|\Exception
     */
    public function getPaymentAuthorizationData($billId): array
    {
        try {
            $bill = $this->loadBill($billId);
            $provider = $this->getProviderByCode($bill->getPaymentMethod());
        } catch (\Throwable $e) {
            throw new \RuntimeException('Wrong request for payment gateway.');
        }
        try {
            $data = $provider->authorize($this, $bill);
        } catch (\RuntimeException $e) {
            if ($orderBundle = $bill->getBundle()
                and $orderBundle->getStatusList()->exists(StatusEnum::build(StatusEnum::CANCELLED))
            ) {
                $msg = 'We\'re apologise, but your Order has been canceled and can\'t be paid.';
                //$this->paymentService->sendEmailPaymentReport($payment, $bill->getBundle());
            } elseif (\in_array($bill->getStatus(), [OrderBill::STATUS_PAID, OrderBill::STATUS_OVERDUE], true)) {
                $msg = 'Sorry, this payment has been already received.';
            }
            throw new \RuntimeException($msg);
        }
        return ['data' => $data, 'payment' => $bill, 'provider' => $provider];
    }

    /**
     * @param $request
     * @param $handler
     *
     * @return bool
     */
    public function confirmPayment(Request $request, string $handler): bool
    {
        $provider = $this->getProviderByCode($handler);
        $provider->capture($this, $request);
        return true;
    }

    /**
     * @return array|PaymentProviderInterface[]
     */
    public function getProvidersList() : array
    {
        return $this->getProviderList()->getValues();
    }

    /**
     * @param string $code
     * @return PaymentProviderInterface
     * @throws \RuntimeException
     */
    public function getProviderByCode(string $code) : PaymentProviderInterface
    {
        $provider = $this->getProviderList()->get($code);
        if (!$provider) {
            throw new \RuntimeException(sprintf('Provider (code: %s) doesn\'t exist in boodmo system', $code));
        }
        return $provider;
    }

    /**
     * @param $paymentId
     *
     * @return \Boodmo\Sales\Entity\Payment
     * @throws \RuntimeException
     */
    public function getPayment($paymentId): Payment
    {
        $payment = $this->paymentRepository->find($paymentId);
        if ($payment === null) {
            throw new \RuntimeException('Payment doesn\'t exist in boodmo system (id: '.$paymentId.')');
        }

        return $payment;
    }

    /**
     * @param string $creditPointId
     * @return CreditPoint|object
     * @throws \Exception
     */
    public function getCreditPoint($creditPointId): CreditPoint
    {
        $creditPoint = $this->creditPointRepository->find($creditPointId);
        if ($creditPoint === null) {
            throw new \RuntimeException('Credit point doesn\'t exist in boodmo system (id: '.$creditPointId.')');
        }
        return $creditPoint;
    }

    /**
     * @param array $options
     *
     * @return Payment[]|array
     */
    public function loadPayments(array $options = [])
    {
        return $this->paymentRepository->getPayments($options);
    }

    /**
     * @param string $id
     * @return OrderBill|object
     * @throws \Exception
     */
    public function loadBill(string $id) : OrderBill
    {
        $bill = $this->orderBillRepository->find($id);
        if ($bill === null) {
            throw new \Exception(sprintf('Bill (id: %s) doesn\'t exist in boodmo system', $id), 422);
        }
        return $bill;
    }

    /**
     * @param string $id
     * @return OrderPaymentApplied|object
     * @throws \Exception
     */
    public function loadPaymentApplied(string $id) : OrderPaymentApplied
    {
        $paymentApplied = $this->orderPaymentAppliedRepository->find($id);
        if ($paymentApplied === null) {
            throw new \Exception(sprintf('Applied payment (id: %s) doesn\'t exist in boodmo system', $id), 422);
        }
        return $paymentApplied;
    }

    /**
     * @param string $id
     * @return OrderCreditPointApplied|object
     * @throws \Exception
     */
    public function loadCreditPointApplied(string $id) : OrderCreditPointApplied
    {
        $creditPointApplied = $this->orderCreditPointAppliedRepository->find($id);
        if ($creditPointApplied === null) {
            throw new \Exception(sprintf('Applied credit point (%s) doesn\'t exist in boodmo system', $id), 422);
        }
        return $creditPointApplied;
    }

    /**
     * @param int $customerId
     * @return array
     */
    public function loadCustomerFreePayments(int $customerId): array
    {
        foreach ($this->paymentRepository->getCustomerFreePayments($customerId) as $payment) {
            $payments[] = $this->paymentHydrator->extract($payment);
        }
        return $payments ?? [];
    }

    /**
     * @param int $customerId
     * @return array
     */
    public function loadCustomerFreeCreditPoints(int $customerId): array
    {
        foreach ($this->creditPointRepository->getCustomerFreeCreditPoints($customerId) as $creditPoint) {
            $creditPoints[] = $this->creditPointHydrator->extract($creditPoint);
        }
        return $creditPoints ?? [];
    }

    public function markAsPaid(string $billId, string $transactionId, float $amount = 0, array $history = []): void
    {
        $orderBill = $this->loadBill($billId);
        $bundle    = $orderBill->getBundle();
        $paymentId = (string) Uuid::uuid4();

        $this->commandBus->dispatch(new AddPaymentCommand(
            $bundle->getCustomerProfile()->getId(),
            $amount,
            $orderBill->getCurrency(),
            $orderBill->getPaymentMethod(),
            $transactionId,
            '',
            $paymentId
        ));
        if ($paymentDue = $orderBill->getPaymentDue()) {
            $this->commandBus->dispatch(new PayToBillCommand(
                $orderBill->getId(),
                [[$paymentId, $paymentDue >= $amount ? $amount : ($amount - $paymentDue)]]
            ));
        }

        $this->orderService->save($bundle);

        //TODO need global observer and use PAID event
        $this->autoDropshipping($bundle);

        //email notification
        try {
            $orderBill = $this->loadBill($orderBill->getId());
            $notifyResult = new NotifyResult();
            $notifyResult->addEvent(new NotifyEvent('*->' . 'PAID' . '[' . OrderBill::class . ']', $orderBill));
            $this->orderService->triggerNotification($notifyResult);
        } catch (\Throwable $exception) {
            //hide exception about failing emailing
        }
    }

    public function sendEmailPaymentReport(Payment $payment, OrderBundle $orderBundle)
    {
        $currencyRate = $payment->getCurrencyRate();
        $currency = $payment->getCurrency();
        $paymentId = $payment->getId();
        $paymentMethod = $payment->getPaymentMethod();
        $paymentTotal = $payment->getTotal() / 100;
        $paymentTransaction = $payment->getTransactionId();
        $paymentZohoBookId = $payment->getZohoBooksId();
        $orderId = $orderBundle->getId();
        $customerEmail = $orderBundle->getCustomerEmail();
        $customerProfile = $orderBundle->getCustomerProfile();
        $customerName = $customerProfile->getFirstName();
        $customerLastName = $customerProfile->getLastName();
        $message = $this->emailManager->getEmail();
        $message->setTo('support@boodmo.com');
        $message->setFrom('support@boodmo.com');
        $message->setSubject('Closed order payment');
        $body = "Customer $customerName $customerLastName with email $customerEmail, tried to pay for closed order 
        with Order ID $orderId and next order payment info:
        paymentId - $paymentId
        currencyRate - $currencyRate
        currency - $currency
        paymentMethod - $paymentMethod
        paymentTotal - $paymentTotal
        paymentTransaction - $paymentTransaction
        paymentZohoBookId - $paymentZohoBookId";
        $message->setBody($body);
        $this->emailManager->send($message);
    }

    public function getPaymentAvailability(): PaymentAvailability
    {
        return new PaymentAvailability($this->getProviderList());
    }

    public function getPaymentByTransactionId($transactionId) : ?Payment
    {
        /** @var Payment $payment */
        $payment = $this->paymentRepository->findOneBy(['transactionId' => $transactionId]);
        return $payment;
    }

    public function save(Payment $payment, $flush = true)
    {
        $this->paymentRepository->save($payment, $flush);
    }

    public function saveCreditPoint(CreditPoint $creditPoint, $flush = true)
    {
        $this->creditPointRepository->save($creditPoint, $flush);
    }

    public function saveOrderBill(OrderBill $creditPoint, $flush = true)
    {
        $this->orderBillRepository->save($creditPoint, $flush);
    }

    public function removePaymentApplied(OrderPaymentApplied $paymentApplied, $flush = true)
    {
        $this->orderPaymentAppliedRepository->delete($paymentApplied, $flush);
    }

    public function removeCreditPointApplied(OrderCreditPointApplied $creditPointApplied, $flush = true)
    {
        $this->orderCreditPointAppliedRepository->delete($creditPointApplied, $flush);
    }

    public function autoDropshipping(OrderBundle $bundle)
    {
        foreach ($bundle->getPackages() as $package) {
            if (!$bundle->hasPaymentsDue($package->getCurrency())) {
                $customerUser = $bundle->getCustomerProfile()->getUser();
                foreach ($package->getItems() as $item) {
                    $statusList = $item->getStatusList();
                    if ($statusList->exists(StatusEnum::build(StatusEnum::PROCESSING))) {
                        $this->commandBus->dispatch(
                            new ApproveSupplierItemCommand(
                                $item->getId(),
                                $customerUser
                            )
                        );
                    }
                }
            }
        }
    }
}
