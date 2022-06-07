<?php
namespace Boodmo\Sales\Service;

use Boodmo\Catalog\Entity\SupplierPart;
use Boodmo\Core\Repository\SiteSettingRepository;
use Boodmo\Core\Service\SiteSettingService;
use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Entity\CancelReason;
use Boodmo\Sales\Entity\OrderBid;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Entity\OrderRma;
use Boodmo\Sales\Model\NotifyResult;
use Boodmo\Sales\Model\Workflow\Note\NotesMessage;
use Boodmo\Sales\Model\Workflow\Status\Status;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Model\Workflow\Status\StatusHistoryInterface;
use Boodmo\Sales\Model\Workflow\StatusWorkflow;
use Boodmo\Sales\Repository\CancelReasonRepository;
use Boodmo\Sales\Repository\OrderBundleRepository;
use Boodmo\Sales\Repository\OrderPackageRepository;
use Boodmo\Sales\Repository\OrderRmaRepository;
use Boodmo\Shipping\Service\ShippingService;
use Boodmo\User\Entity\User;
use Boodmo\User\Entity\UserProfile\Customer;
use Boodmo\User\Service\SupplierService;
use Boodmo\User\Service\AddressService;
use Boodmo\User\Service\UserService;
use Boodmo\Sales\Repository\OrderItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Prooph\ServiceBus\CommandBus;
use Money\Currency;
use Boodmo\Sales\Entity\CreditMemo;

class OrderService
{
    /** @var OrderBundleRepository */
    protected $orderRepository;
    /**
     * @var SupplierService
     */
    protected $supplierService;
    protected $orderItemRepository;
    protected $siteSettingsRepository;
    protected $config;
    /**
     * @var ShippingService
     */
    protected $shippingService;
    /**
     * @var AddressService;
     */
    protected $addressService;

    private $cancelReasonRepository;

    private $notificationService;

    /** @var StatusWorkflow */
    private $statusWorkflow;

    /** @var CommandBus */
    private $commandBus;

    /** @var OrderPackageRepository */
    private $packageRepository;

    /** @var MoneyService */
    private $moneyService;

    /** @var OrderRmaRepository */
    private $orderRmaRepository;

    /**
     * @var SiteSettingService
     */
    private $siteSettingService;

    const CREDIT_MEMO_TEMPLATE_ID = 'finance-creditmemo';

    public function __construct(
        OrderBundleRepository $orderRepository,
        OrderPackageRepository $packageRepository,
        SupplierService $supplierService,
        UserService $userService,
        OrderItemRepository $orderItemRepository,
        SiteSettingRepository $siteSettingsRepository,
        ShippingService $shippingService,
        AddressService $addressService,
        $config,
        CancelReasonRepository $cancelReasonRepository,
        NotificationService $notificationService,
        StatusWorkflow $statusWorkflow,
        CommandBus $commandBus,
        MoneyService $moneyService,
        OrderRmaRepository $orderRmaRepository,
        SiteSettingService $siteSettingService
    ) {
        $this->orderRepository = $orderRepository;
        $this->packageRepository = $packageRepository;
        $this->supplierService = $supplierService;
        $this->orderItemRepository = $orderItemRepository;
        $this->siteSettingsRepository = $siteSettingsRepository;
        $this->shippingService = $shippingService;
        $this->config = $config;
        $this->addressService = $addressService;
        $this->userService = $userService;
        $this->cancelReasonRepository = $cancelReasonRepository;
        $this->notificationService = $notificationService;
        $this->statusWorkflow = $statusWorkflow;
        $this->commandBus = $commandBus;
        $this->moneyService = $moneyService;
        $this->orderRmaRepository = $orderRmaRepository;
        $this->siteSettingService = $siteSettingService;
    }

    public function save(OrderBundle $orderBundle, $flush = true)
    {
        $this->orderRepository->save($orderBundle, $flush);
    }

    public function getOrderRepository()
    {
        return $this->orderRepository;
    }

    /**
     * @param int $idOrIncrement
     * @return OrderBundle
     * @throws \Exception
     */
    public function loadOrderBundle(int $idOrIncrement): OrderBundle
    {
        $bundle =  $this->orderRepository->find($idOrIncrement);
        if ($bundle === null) {
            throw new \Exception(sprintf('Order doesn\'t exist (id: %s)', $idOrIncrement));
        }
        return $bundle;
    }
    public function loadAllBundles()
    {
        return $this->orderRepository->findAll();
    }
    public function loadCustomerOrderBundles($customer)
    {
        return $this->orderRepository->findBy(['customerProfile' => $customer], ['createdAt' => 'DESC']);
    }
    public function loadPackage($packageId)
    {
        return $this->packageRepository->find($packageId);
    }

    /**
     * @param $id
     * @return OrderItem|null
     * @throws \Exception
     */
    public function loadOrderItem($id)
    {
        $item = $this->orderItemRepository->find($id);
        if ($item === null) {
            throw new \Exception(sprintf('Undefined order item (id: %s)', $id));
        }
        return $item;
    }
    public function loadSalesCancelReason(int $reasonId) : CancelReason
    {
        return $this->cancelReasonRepository->find($reasonId);
    }
    public function getRmaRepository()
    {
        return $this->orderRmaRepository;
    }
    public function getOrderPackageRepository()
    {
        return $this->packageRepository;
    }

    /**
     * Function: updateItemPricesBySupPart
     *
     * @param OrderItem $item
     * @param SupplierPart $supPart
     * @param int $deliveryPrice
     *
     * @return OrderItem
     */
    public function updateItemPricesBySupPart(OrderItem $item, SupplierPart $supPart, int $deliveryPrice) : OrderItem
    {
        return $this->updateItemPrices($item, $supPart->getPrice() * 100, $supPart->getCost() * 100, $deliveryPrice);
    }

    /**
     * Function: updateItemPrices
     *
     * @param OrderItem $item
     * @param int $price
     * @param int $cost
     * @param int $delivery
     *
     * @return OrderItem
     */
    public function updateItemPrices(OrderItem $item, int $price, int $cost, int $delivery) : OrderItem
    {
        $item->setPrice($price);
        $item->setCost($cost);
        $item->setDeliveryPrice($delivery);

        $baseCurrency = new Currency(MoneyService::BASE_CURRENCY);
        $price = (int)$this->moneyService->convert($item->getMoney($price), $baseCurrency)->getAmount();
        $cost = (int) $this->moneyService->convert($item->getMoney($cost), $baseCurrency)->getAmount();
        $delivery = (int) $this->moneyService->convert($item->getMoney($delivery), $baseCurrency)->getAmount();

        $item->setBasePrice($price);
        $item->setBaseCost($cost);
        $item->setBaseDeliveryPrice($delivery);

        return $item;
    }

    //-----------------Functions for new workflow-----------------------------
    // all price for V1 mobile api
    //TODO seems can be removed
    public function loadPackageDetailById($options)
    {
        $entity = $this->loadPackage($options['id']);
        if ($entity) {
            //General information
            $bundle   = $entity->getBundle();
            $supplier = $entity->getSupplierProfile();
            $customer = $bundle->getCustomerAddress();
            $items    = $entity->getItems();
            $i        = 0;
            //Shipping provider information
            $shipCode = null;
            $trackNumber = null;
            if ($shippingBox = $entity->getShippingBox()) {
                $shipCode = $shippingBox->getMethod();
                $trackNumber = $shippingBox->getTrackNumber();
            }
            $package['provider'] = $shipCode
                ? $this->shippingService->getProviderByCode($shipCode)->getProviderName()
                : null;
            $package['carrier'] = $shipCode
                ? $this->shippingService->getCarrierByCode($shipCode)->getCarrierName()
                : null;
            $package['track_number'] = $trackNumber;
            $package['deliveryTotal'] = $entity->getBaseDeliveryTotal() / 100;
            $package['subTotal'] = $entity->getBaseSubTotal() / 100;
            $package['invoiceTotal'] = $entity->getBaseGrandTotal() / 100;
            //$package['costTotal'] = $entity->getBaseCostTotal() / 100;
            $package['deliveryDays'] = $entity->getDeliveryDays();
            $package['shipping_eta'] = $entity->getShippingETA();
            //Payments information
            $package['payments'] = [];
            /*$package['payments'] = $bundle->getArrayPaymentsInfo()['payments'];
            if (!empty($package['payments'])) {
                foreach ($package['payments'] as $paymentIndex => $payment) {
                    $package['payments'][$paymentIndex]['total'] = $payment['total'] / 100;
                }
            }*/
            //Items information
            $products = [];
            foreach ($items as $item) {
                $partId = (int)$item->getPartId();

                $products[$i]['item_id']             = $item->getId();
                //$products[$i]['history']             = $item->getWorkflowHistory();
                $products[$i]['name']                = $item->getName();
                $products[$i]['qty']                 = $item->getQty();
                $products[$i]['part_id']             = $partId;
                $products[$i]['brand']               = $item->getBrand();
                //$products[$i]['cost']                = $item->getCost();
                $products[$i]['number']              = $item->getNumber();
                $products[$i]['price']               = $item->getBasePrice() / 100;
                $products[$i]['delivery_price']      = $item->getBaseDeliveryPrice() / 100;
                $products[$i]['dropshipping_status'] = strtolower($item->getStatusList()
                    ->fallbackStatus(Status::TYPE_GENERAL)
                    ->getName());
                $products[$i]['supplier_status']     = $item->getStatusList()
                    ->fallbackStatus(Status::TYPE_SUPPLIER)
                    ->getName();
                $products[$i]['createdAt']           = $item->getCreatedAt()->getTimestamp();
                // TODO: part-part - tmp solution for working without route.
                // will be redirected to normal url by system
                $products[$i]['link']                = $this->config['serverUrl'].'/catalog/part-part-'.$partId;

                $i++;
            }
            $package['items'] = $products;
            //Other information
            $package['delivery']   = $entity->getDeliveryDays();
            $package['number']     = $entity->getFullNumber();
            $package['order_id']   = $bundle->getNumber();
            $package['address']    = $bundle->getCustomerAddress();
            $package['pay_method'] = $bundle->getPaymentMethod();
            $package['notes']      = $entity->getNotes();
            $package['supplier']   = [
                'id'   => $supplier->getId(),
                'name' => $supplier->getName()
            ];
            $package['customer'] = [
                'phone' => $customer['phone'],
                'name'  => $customer['first_name'] . " " . $customer['last_name'],
                'id'    => $bundle->getCustomerProfile()->getId(),
                'email' => $bundle->getCustomerEmail(),
                'ip'    => $bundle->getClientIp()
            ];
        } else {
            throw new \Exception(sprintf('You try to find undefined package (id: %s)', $options['id']), 422);
        }
        return $package;
    }

    public function triggerNotification(NotifyResult $notifyResult): void
    {
        $notifyResult->triggerEvents($this->notificationService->getEventManager());
    }

    /**
     * @return StatusWorkflow
     */
    public function getStatusWorkflow(): StatusWorkflow
    {
        return $this->statusWorkflow;
    }

    public function loadCustomerRma(int $customerId, ?string $orderItemId = null): array
    {
        if ($orderItemId === null) {
            $result = $this->orderRmaRepository->findByCustomer($customerId);
        } else {
            $result = $this->orderRmaRepository->findBy(['orderItem' => $orderItemId], ['createdAt' => 'DESC']);
        }
        return $result;
    }

    /**
     * @param string $id
     * @return OrderRma
     * @throws \Exception
     */
    public function loadOrderRma(string $id): OrderRma
    {
        $rma = $this->orderRmaRepository->find($id);
        if ($rma === null) {
            throw new \Exception(sprintf('Undefined order rma (id: %s)', $id));
        }
        return $rma;
    }

    public function isOrderItemBelongToCustomer(OrderItem $orderItem, Customer $customer): bool
    {
        $result = false;
        if ($bundle = $orderItem->getPackage()->getBundle()
            and $bundleCustomer = $bundle->getCustomerProfile()
            and $customer->getId() === $bundleCustomer->getId()
        ) {
            $result = true;
        }
        return $result;
    }

    public function isOrderRmaBelongToCustomer(OrderRma $orderRma, Customer $customer): bool
    {
        $result = false;
        if ($bundleCustomer = $orderRma->getOrderItem()->getPackage()->getBundle()->getCustomerProfile()
            and $customer->getId() === $bundleCustomer->getId()
        ) {
            $result = true;
        }
        return $result;
    }

    public function isCustomerHasCompleteOrders(Customer $customer): bool
    {
        return $this->getOrderRepository()->isCustomerHasCompleteOrders($customer->getId());
    }

    public function isCustomerHasRma(Customer $customer): bool
    {
        return $this->getRmaRepository()->isCustomerHasRma($customer->getId());
    }

    public static function isAllowCreateRmaByPeriod(OrderItem $item): bool
    {
        $nowDate = new \DateTime();
        $shippingBox = $item->getPackage() ? $item->getPackage()->getShippingBox() : null;
        $statusList = $item->getStatusList();
        $isCorrectStatus = $statusList->exists(StatusEnum::build(StatusEnum::COMPLETE))
            && $statusList->exists(StatusEnum::build(StatusEnum::DELIVERED));
        return $isCorrectStatus
            and $shippingBox
            and $deliveredDate = $shippingBox->getDeliveredAt()
            and $deliveredDate->add(new \DateInterval('P'.OrderRma::ALLOWED_COUNT_DAYS_FOR_RETURN.'D')) > $nowDate;
    }

    public static function isAllowCancelOrderItemByCustomer(OrderItem $item): bool
    {
        $statusList = $item->getStatusList();
        return !$statusList->exists(StatusEnum::build(StatusEnum::CANCELLED))
            && !$statusList->exists(StatusEnum::build(StatusEnum::COMPLETE))
            && !$statusList->exists(StatusEnum::build(StatusEnum::CANCEL_REQUESTED_USER))
            && !$statusList->exists(StatusEnum::build(StatusEnum::SENT_TO_LOGISTICS));
    }

    public static function isAllowCancelOrderByCustomer(OrderBundle $order): bool
    {
        $result = true;
        $hasAnyCancellableItem = false;
        foreach ($order->getPackages() as $package) {
            foreach ($package->getItems() as $item) {
                $statusList = $item->getStatusList();
                //do not allow cancel order with not cancellable items
                if ($statusList->exists(StatusEnum::build(StatusEnum::COMPLETE))
                    || $statusList->exists(StatusEnum::build(StatusEnum::SENT_TO_LOGISTICS))
                ) {
                    $result = false;
                    break;
                }
                if (self::isAllowCancelOrderItemByCustomer($item)) {
                    $hasAnyCancellableItem = true;
                }
            }
        }
        return $hasAnyCancellableItem ? $result : false;
    }

    public function getDefaultConfirmationDateForItem(): \DateTimeImmutable
    {
        $confirmationDate = new \DateTimeImmutable('now');
        $supplierConfirmationPeriod = (int)$this->siteSettingService
            ->getSettingByPath('general/supplier_confirmation_period');
        $supplierConfirmationPeriod = $supplierConfirmationPeriod ?: 1;
        return $confirmationDate->add(new \DateInterval('P'.$supplierConfirmationPeriod.'D'));
    }

    /**
     * Move bids from old item to new in one bundle
     * @param OrderItem $fromOrderItem
     * @param OrderItem $toOrderItem
     * @param bool $createAccepted
     */
    public function moveBids(OrderItem $fromOrderItem, OrderItem $toOrderItem, bool $createAccepted = true): void
    {
        $hasAccepted = false;
        foreach ($fromOrderItem->getBids() as $bid) {
            $toOrderItem->addBid($bid);
            $fromOrderItem->removeBid($bid);

            if ($bid->getStatus() === OrderBid::STATUS_ACCEPTED) {
                $hasAccepted = true;
            }
        }
        if ($createAccepted && !$hasAccepted) {
            $toOrderItem->addBid($toOrderItem->createAcceptedBid());
        }
        if ($package = $fromOrderItem->getPackage() and $bundle = $package->getBundle()) {
            $this->save($bundle);
        }
    }

    /**
     * Add notes about changing cost
     * @param OrderItem $orderItem
     * @param OrderItem $newOrderItem
     * @param User      $author
     */
    public function addNoticeAboutCost(OrderItem $orderItem, OrderItem $newOrderItem, User $author): void
    {
        if ($orderItem->isCancelled()) {
            $messages = [];
            $orderPackageCurrency = $orderItem->getPackage() ? $orderItem->getPackage()->getCurrency() : '';
            $newOrderPackageCurrency = $newOrderItem->getPackage() ? $newOrderItem->getPackage()->getCurrency() : '';
            if ($orderItem->getNumber() !== $newOrderItem->getNumber()
                || $orderItem->getBrand() !== $newOrderItem->getBrand()
            ) {
                $messages[] = sprintf(
                    'item change from %s %s to %s %s',
                    $orderItem->getBrand(),
                    $orderItem->getNumber(),
                    $newOrderItem->getBrand(),
                    $newOrderItem->getNumber()
                );
            }
            if ($orderItem->getPrice() !== $newOrderItem->getPrice()) {
                $messages[] = sprintf(
                    'the price change from %s %s to %s %s',
                    $orderItem->getPrice() / 100,
                    $orderPackageCurrency,
                    $newOrderItem->getPrice() / 100,
                    $newOrderPackageCurrency
                );
            }
            if ($orderItem->getCost() !== $newOrderItem->getCost()) {
                $messages[] = sprintf(
                    'the cost change from %s %s to %s %s',
                    $orderItem->getCost() / 100,
                    $orderPackageCurrency,
                    $newOrderItem->getCost() / 100,
                    $newOrderPackageCurrency
                );
            }
            if ($orderItem->getDeliveryTotal() !== $newOrderItem->getDeliveryTotal()) {
                $messages[] = sprintf(
                    'the change of delivery cost from %s %s to %s %s',
                    $orderItem->getDeliveryTotal() / 100,
                    $orderPackageCurrency,
                    $newOrderItem->getDeliveryTotal() / 100,
                    $newOrderPackageCurrency
                );
            }
            if ($orderItem->getQty() !== $newOrderItem->getQty()) {
                $messages[] = sprintf(
                    'the quantity change from %s to %s',
                    $orderItem->getQty(),
                    $newOrderItem->getQty()
                );
            }
            if (!empty($messages)) {
                $orderItem->addMessageToNotes(
                    new NotesMessage('SALES', 'Item was cancelled because of '.implode(', ', $messages).'.', $author)
                );
            }
        }
    }

    /**
     * Return only applied payments and creditPoints
     * @param OrderBundle $orderBundle
     * @return ArrayCollection
     */
    public function createCreditMemo(OrderBundle $orderBundle): ArrayCollection
    {
        $creditMemos = new ArrayCollection();

        if (!$orderBundle->getStatusList()->exists(StatusEnum::build(StatusEnum::CANCELLED))
            && !$orderBundle->getStatusList()->exists(StatusEnum::build(StatusEnum::COMPLETE))) {
            return $creditMemos;
        }

        $message = "\n\nCalculation: \n";
        $message .= "Paid payments:\n";
        $paymentsSum = [];
        foreach ($orderBundle->getBills() as $bills) {
            foreach ($bills->getPaymentsApplied() as $paymentApplied) {
                $payment = $paymentApplied->getPayment();
                // Sum paid payment by currency
                $paymentCurrency = $payment->getCurrency();
                $paymentsSum[$paymentCurrency] = ($paymentsSum[$paymentCurrency] ?? 0)
                    + $paymentApplied->getAmount();
                $message .= '* Total: ' . $paymentApplied->getAmount()
                    .'; Currency: ' . $paymentCurrency
                    .'; TransactionID: ' . $payment->getTransactionId()
                    .'; Last updated (paid) date: ' . $payment->getUpdatedAt()->format('Y-m-d H:i')
                    ."\n";
            }
        }

        $message .= "\nInvoice totals by packages:\n";
        foreach ($orderBundle->getPackages() as $package) {
            $packageCurrency = $package->getCurrency();
            if (isset($paymentsSum[$packageCurrency])) {
                $paymentsSum[$packageCurrency] -= $package->getGrandTotal();
            }
            $message .= '* Total: ' . $package->getGrandTotal()
                . '; Currency: ' . $packageCurrency
                . '; Number: ' . $package->getFullNumber()
                . '; Last updated date: ' . $package->getUpdatedAt()->format('Y-m-d H:i')
                . "\n";
        }

        $message .= "\nInvoice totals by credit points:\n";
        foreach ($orderBundle->getBills() as $bills) {
            foreach ($bills->getCreditPointsApplied() as $creditPointApplied) {
                $creditPoint = $creditPointApplied->getCreditPoint();
                $creditPointCurrency = $creditPoint->getCurrency();
                $paymentsSum[$creditPointCurrency] = ($paymentsSum[$creditPointCurrency] ?? 0)
                    + $creditPointApplied->getAmount();

                $message .= '* Total: ' . $creditPointApplied->getAmount()
                    . '; Currency: ' . $creditPointCurrency
                    . '; Last updated date: ' . $creditPoint->getUpdatedAt()->format('Y-m-d H:i')
                    . "\n";
            }
        }

        $creditPointsAppliedMoney = $orderBundle->getCreditPointsAppliedMoney();
        foreach ($paymentsSum as $currency => $total) {
            if ($total <= 0) {
                continue;
            }

            $creditMemo = new CreditMemo();
            $creditMemo->setTotal($total)
                ->setCurrency($currency)
                ->setOpen(true);
            if (isset($creditPointsAppliedMoney[$currency])) {
                $message .= 'Important! For this order was use Credit Points: '.
                    $creditPointsAppliedMoney[$currency]->getAmount() / 100 . "\n";
            }
            $orderBundle->addCreditMemo($creditMemo);
            $financeEmail = $this->siteSettingService->getSettingByPath('general/finance_form_email');
            $vars = [
                'order' => [
                    'order_number' => $orderBundle->getNumber(),
                    'total' => abs($total / 100),
                    'currency' => $currency,
                    'email' => $financeEmail,
                    'message' => $message,
                ],
            ];
            $this->notificationService->sendCreditMemoNotification(self::CREDIT_MEMO_TEMPLATE_ID, $vars);

            $creditMemos->add($creditMemo);
        }

        $this->save($orderBundle);
        return $creditMemos;
    }

    public static function getTrackPackageData(OrderPackage $package): array
    {
        $order = $package->getBundle();
        $shippingBox = $package->getShippingBox();
        $packageStatus = $package->getStatus();
        $packageStatusHistory = $package->getStatusHistory();
        $packageCustomerStatusName = $package->getCustomerStatusName();
        $isPackageCancelled = $packageCustomerStatusName === 'Cancelled';
        $isPackageCancelRequested = $packageCustomerStatusName === 'Cancel Requested';
        $isPackageCompleted = isset($packageStatus[Status::TYPE_GENERAL])
            && $packageStatus[Status::TYPE_GENERAL] === StatusEnum::COMPLETE;

        $isCustomerStatusPresent = isset($packageStatus[Status::TYPE_CUSTOMER]);

        $steps = [
            //Placed on
            1 => [
                'status' => true,
                'date' => $order->getCreatedAt(),
                'show' => true
            ],
            //Processing
            2 => [
                'status' => $isCustomerStatusPresent
                    && \in_array($packageStatus[Status::TYPE_CUSTOMER], [StatusEnum::CUSTOMER_PROCESSING, StatusEnum::CUSTOMER_READY_TO_SEND, StatusEnum::CUSTOMER_DISPATCHED], true)
                ,
                'date' => null,
                'show' => false,
            ],
            //Ready to Send
            3 => [
                'status' => $isCustomerStatusPresent
                    && (
                        ($packageStatus[Status::TYPE_CUSTOMER] === StatusEnum::CUSTOMER_READY_TO_SEND && !$isPackageCancelRequested)
                        || $packageStatus[Status::TYPE_CUSTOMER] === StatusEnum::CUSTOMER_DISPATCHED
                    ),
                'date' => null,
                'show' => false,
            ],
            //Dispatched / Expected dispatch date
            4 => [
                'status' => $isCustomerStatusPresent
                    && $packageStatus[Status::TYPE_CUSTOMER] === StatusEnum::CUSTOMER_DISPATCHED
                    && !$isPackageCancelRequested,
                'date' => null,
                'show' => false,
            ],
            //Delivered / Expected delivery date
            5 => [
                'status' => $isPackageCompleted,
                'date' => null,
                'show' => false,
            ],
            //Cancelled
            6 => [
                'status' => $isPackageCancelled,
                'date' => null,
                'show' => false,
            ]
        ];

        $dropshipCounter = 0;
        $dropship_date = null;
        foreach ($packageStatusHistory as $row) {
            if (!empty($row[StatusHistoryInterface::TO]) && \is_array($row[StatusHistoryInterface::TO])) {
                if (\in_array(StatusEnum::DROPSHIPPED, $row[StatusHistoryInterface::TO], true)) {
                    $dropshipCounter++;
                    $new_date = (new \DateTime())->setTimestamp($row[StatusHistoryInterface::TIMESTAMP]);
                    $dropship_date = \max($dropship_date, $new_date);
                }
                if (($steps[2]['status'] || $isPackageCancelled || $isPackageCompleted)
                    && \in_array(StatusEnum::CUSTOMER_PROCESSING, $row[StatusHistoryInterface::TO], true)
                ) {
                    $new_date = (new \DateTime())->setTimestamp($row[StatusHistoryInterface::TIMESTAMP]);
                    $steps[2]['date'] = \max($steps[2]['date'], $new_date);
                    $steps[2]['status'] = true;
                }
                if (($steps[3]['status'] || $isPackageCancelled || $isPackageCompleted)
                    && \in_array(StatusEnum::CUSTOMER_READY_TO_SEND, $row[StatusHistoryInterface::TO], true)
                ) {
                    $new_date = (new \DateTime())->setTimestamp($row[StatusHistoryInterface::TIMESTAMP]);
                    $steps[3]['date'] = \max($steps[3]['date'], $new_date);
                    $steps[3]['status'] = true;
                }
                if (($steps[4]['status'] || $isPackageCancelled || $isPackageCompleted)
                    && \in_array(StatusEnum::CUSTOMER_DISPATCHED, $row[StatusHistoryInterface::TO], true)
                ) {
                    $steps[4]['status'] = true;
                }
                if (\in_array(StatusEnum::CANCELLED, $row[StatusHistoryInterface::TO], true)) {
                    $steps[6]['date'] = (new \DateTime())->setTimestamp($row[StatusHistoryInterface::TIMESTAMP]);
                }
            }
        }
        //"Processing" date for packages after vendorChange
        if ($dropshipCounter > 1) {
            $steps[2]['date'] = $dropship_date;
        }
        if ($shippingBox && !$isPackageCancelRequested) {
            $steps[4]['date'] = $shippingBox->getDispatchedAt();
            $steps[5]['date'] = $shippingBox->getDeliveredAt();
        }
        if (!$steps[4]['status'] && !$isPackageCancelRequested) {
            $date = null;
            foreach ($package->getItems() as $packageItem) {
                $date = $packageItem->getDispatchDate() > $date ? $packageItem->getDispatchDate() : $date;
            }
            $steps[4]['date'] = $date;
        }
        if (!$steps[5]['status'] && !$isPackageCancelRequested) {
            $steps[5]['date'] = $package->getShippingETA();
        }

        $steps[2]['show'] = !$isPackageCancelled || ($isPackageCancelled && $steps[2]['date']);
        $steps[3]['show'] = !$isPackageCancelled || ($isPackageCancelled && $steps[3]['date']);
        $steps[4]['show'] = !$isPackageCancelled || ($isPackageCancelled && $steps[4]['status'] && !empty($steps[4]['date']));
        $steps[5]['show'] = !$isPackageCancelled;
        $steps[6]['show'] = $isPackageCancelled;

        return $steps;
    }
}
