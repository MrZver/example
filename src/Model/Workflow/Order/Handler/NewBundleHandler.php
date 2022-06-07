<?php

namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Model\Payment\Provider\CashProvider;
use Boodmo\Sales\Model\Workflow\Order\Command\NewBundleCommand;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Service\CheckoutService;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Sales\Service\PaymentService;
use Boodmo\Seo\Service\BetaoutService;
use Boodmo\User\Service\CustomerService;
use Doctrine\ORM\EntityManager;

final class NewBundleHandler
{
    /**
     * @var OrderService
     */
    private $orderService;
    /**
     * @var CheckoutService
     */
    private $checkoutService;
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var CustomerService
     */
    private $customerService;
    /**
     * @var PaymentService
     */
    private $paymentService;

    /**
     * @var BetaoutService
     */
    private $betaoutService;

    /**
     * Constructor.
     *
     * @param OrderService    $orderService
     * @param CheckoutService $checkoutService
     * @param EntityManager   $entityManager
     * @param CustomerService $customerService
     * @param PaymentService  $paymentService
     * @param BetaoutService  $betaoutService
     */
    public function __construct(
        OrderService $orderService,
        CheckoutService $checkoutService,
        EntityManager $entityManager,
        CustomerService $customerService,
        PaymentService $paymentService,
        BetaoutService $betaoutService
    ) {
        $this->orderService = $orderService;
        $this->checkoutService = $checkoutService;
        $this->entityManager = $entityManager;
        $this->customerService = $customerService;
        $this->paymentService = $paymentService;
        $this->betaoutService = $betaoutService;
    }

    /**
     * @param NewBundleCommand $command
     * @throws \Exception
     */
    public function __invoke(NewBundleCommand $command): void
    {
        $cart = $command->getCart();
        $additionalInfo = $command->getAdditionalInfo();
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $this->customerService->autoRegister($cart->getEmail(), [
                'address' => $cart->getAddress()->toArray(),
                'firstName' => $cart->getAddress()->getFirstName(),
                'lastName' => $cart->getAddress()->getLastName(),
                'phone' => $cart->getAddress()->getPhone(),
                'pin' => $cart->getAddress()->getPin(),
                'cohortSource' => $additionalInfo['cohortSource'] ?? null,
                'cohortMedium' => $additionalInfo['cohortMedium'] ?? null,
            ]);

            //1. Create new order
            $orderBundle = $this->checkoutService->convertCartToOrder($cart, $command->getUser())
                ->setGaCid($additionalInfo['gacid'] ?? '')
                ->setAffiliate($additionalInfo['affiliate'] ?? 'web')
                ->setClientIp($additionalInfo['client_ip'] ?? '');
            ($this->checkoutService->getShoppingRuleProcessor())($orderBundle);

            foreach ($cart->getPaymentMethods() as $code) {
                $provider = $this->paymentService->getProviderByCode($code);

                $total = $baseTotal = 0;
                /** @var OrderPackage $package */
                foreach ($orderBundle->getPackagesWithCurrency($provider->getBaseCurrency()) as $package) {
                    $total += $package->getGrandTotal();
                    $baseTotal += $package->getBaseGrandTotal();
                }

                $bill = new OrderBill();
                $bill->setTotal($total)
                    ->setBaseTotal($baseTotal)
                    ->setPaymentMethod($provider->getCode())
                    ->setCurrency($provider->getBaseCurrency())
                    ->setType($code !== CashProvider::CODE ? OrderBill::TYPE_PREPAID : OrderBill::TYPE_ON_DELIVERY);

                $orderBundle->addBill($bill);
            }

            $items = [];
            foreach ($orderBundle->getPackages() as $package) {
                $items = array_merge($items, $package->getItems()->toArray());
            }
            $statusWorkflow = $this->orderService->getStatusWorkflow();
            $options = [
                TransitionEventInterface::CONTEXT => [
                    'author' => $orderBundle->getCustomerEmail(),
                    'action' => EventEnum::NEW_ORDER,
                ]
            ];
            $result = $statusWorkflow->raiseTransition(
                EventEnum::build(EventEnum::NEW_ORDER, $statusWorkflow->buildInputItemList($items), $options)
            );
            $this->orderService->save($orderBundle);
            $this->betaoutService->processOrderPlace($orderBundle);

            //4. Commit
            $this->entityManager->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->entityManager->getConnection()->rollBack();
            throw new \Exception(sprintf('Internal Server Error: %s', $e->getMessage()), 0, $e);
        }

        $cart->clearAll($orderBundle->getId());
        $this->orderService->triggerNotification($result);
    }
}
