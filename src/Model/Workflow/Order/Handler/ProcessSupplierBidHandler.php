<?php

namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Catalog\Service\SupplierPartService;
use Boodmo\Core\Service\SiteSettingService;
use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Entity\OrderBid;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Model\Workflow\Note\NotesMessage;
use Boodmo\Sales\Model\Workflow\Order\Command\ProcessSupplierBidCommand;
use Boodmo\Sales\Model\Workflow\Order\Command\SupplierHubReadyShippingCommand;
use Boodmo\Sales\Model\Workflow\Order\Command\SupplierReadyDeliveryItemCommand;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\Status;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Repository\OrderBidRepository;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Shipping\Service\ShippingService;
use Boodmo\User\Entity\User;
use Boodmo\User\Service\SupplierService;
use Money\Currency;
use Prooph\ServiceBus\CommandBus;

class ProcessSupplierBidHandler
{
    public const NEXT_HANDLERS_COMMANDS = [
        'shipping_ready' => SupplierReadyDeliveryItemCommand::class,
        'shipping_ready_hub' => SupplierHubReadyShippingCommand::class,
    ];
    /** @var OrderService */
    private $orderService;

    /** @var OrderBidRepository */
    private $orderBidRepository;

    /** @var  SupplierService */
    private $supplierService;

    /** @var ShippingService */
    private $shippingService;

    /** @var SiteSettingService */
    private $siteSettingService;

    /** @var CommandBus */
    private $commandBus;

    /**
     * @var SupplierPartService
     */
    private $supplierPartService;

    /**
     * Constructor
     *
     * @param OrderService $orderService
     * @param OrderBidRepository $orderBidRepository
     * @param SupplierService $supplierService
     * @param ShippingService $shippingService
     * @param SiteSettingService $siteSettingService
     * @param MoneyService $moneyService
     * @param CommandBus $commandBus
     * @param SupplierPartService $supplierPartService
     */
    public function __construct(
        OrderService $orderService,
        OrderBidRepository $orderBidRepository,
        SupplierService $supplierService,
        ShippingService $shippingService,
        SiteSettingService $siteSettingService,
        MoneyService $moneyService,
        CommandBus $commandBus,
        SupplierPartService $supplierPartService
    ) {
        $this->orderService = $orderService;
        $this->orderBidRepository = $orderBidRepository;
        $this->supplierService = $supplierService;
        $this->shippingService = $shippingService;
        $this->siteSettingService = $siteSettingService;
        $this->moneyService = $moneyService;
        $this->commandBus = $commandBus;
        $this->supplierPartService = $supplierPartService;
    }

    /**
     * @param ProcessSupplierBidCommand $command
     * @throws \Exception
     */
    public function __invoke(ProcessSupplierBidCommand $command)
    {
        /** @var OrderItem $orderItem */
        $orderItem = $this->orderService->loadOrderItem($command->getItemId());
        $bundle    = $orderItem->getPackage()->getBundle();

        $orderBid = $this->getBid($orderItem, $command);
        $allowOpenBid = $this->allowOpenBid($orderItem, $orderBid, $command->getPrice(), $command->getDispatchDate());

        $orderBid->setCost($command->getCost())
            ->setPrice($command->getPrice())
            ->setDispatchDate($command->getDispatchDate());

        //CANCEL_REQUESTED_SUPPLIER - when supplier has worsen the conditions
        if ($orderBid->getStatus() !== OrderBid::STATUS_OPEN && $allowOpenBid) {
            $orderBid->setStatus(OrderBid::STATUS_OPEN);
            $this->processWorsenConditions($orderItem, $command->getEditor());
        }

        if ($orderBid->getStatus() === OrderBid::STATUS_ACCEPTED) {
            $this->processImproveConditions($orderItem, $orderBid, $command);
        }

        $this->orderBidRepository->save($orderBid);
    }

    private function getBid(OrderItem $orderItem, ProcessSupplierBidCommand $command): OrderBid
    {
        $orderBid = null;
        if ($command->getBidId() !== null) {
            $orderBid = $this->orderBidRepository->find($command->getBidId());
        }
        if ($orderBid === null) {
            $supplier = $this->supplierService->loadSupplierProfile($command->getSupplier());
            $logistic = $this->shippingService->getLogisticsFromSupplierToCustomer(
                $supplier,
                $orderItem->getPackage()->getBundle()
            );
            $orderBid = (new OrderBid())
                ->setSupplierProfile($supplier)
                ->setOrderItem($orderItem)
                ->setDeliveryDays($logistic->getDays())
                ->setBrand($command->getBrand() ?? $orderItem->getBrand())
                ->setNumber($command->getNumber() ?? $orderItem->getNumber())
                ->setGst($command->getGst())
                ->setStatus(OrderBid::STATUS_OPEN);
        }
        if (!empty($command->getNotes()['text'])) {
            $message = new NotesMessage(
                OrderBid::NOTE_CONTEXT,
                $command->getNotes()['text'],
                (new User())->setEmail($command->getNotes()['author'])
            );
            $orderBid->addMessageToNotes($message);
        }
        return $orderBid;
    }

    /**
     * @param OrderItem $orderItem
     * @param User $user
     * @throws \Exception
     */
    public function processWorsenConditions(OrderItem $orderItem, User $user): void
    {
        $statusWorkflow = $this->orderService->getStatusWorkflow();
        $status = $orderItem->getStatusList()->fallbackStatus(Status::TYPE_SUPPLIER);
        $event = null;
        switch ($status->getCode()) {
            case StatusEnum::SUPPLIER_NEW:
                $event = EventEnum::SUPPLIER_CANCEL_NEW;
                break;
            case StatusEnum::CONFIRMED:
                $event = EventEnum::SUPPLIER_CANCEL_CONFIRMED;
                break;
        }
        if ($event) {
            $options = [
                TransitionEventInterface::CONTEXT => [
                    'author' => $user->getEmail(),
                    'action' => $event,
                ]
            ];
            $statusWorkflow->raiseTransition(
                EventEnum::build($event, $statusWorkflow->buildInputItemList([$orderItem]), $options)
            );
        }
    }

    /**
     * @param OrderItem $orderItem
     * @param OrderBid $orderBid
     * @param ProcessSupplierBidCommand $command
     * @throws \Exception
     */
    private function processImproveConditions(
        OrderItem $orderItem,
        OrderBid $orderBid,
        ProcessSupplierBidCommand $command
    ): void {
        //Prepare new price/cost for supplier_part
        $supplierPart = $this->supplierPartService->prepareNewSupplierPart(
            $orderItem->getPartId(),
            $command->getPrice(),
            $command->getCost(),
            $orderItem->getPackage()->getCurrency(),
            $orderBid->getSupplierProfile()
        );
        $orderItem->getPackage()->setDeliveryDays($orderBid->getDeliveryDays() ?? 1);
        //Prepare new price/cost/dispatchDate for item
        $this->orderService->updateItemPrices(
            $orderItem,
            $command->getPrice(),
            $command->getCost(),
            $orderItem->getDeliveryPrice()
        )
            ->setDispatchDate($orderBid->getDispatchDate())
            ->setProductId($supplierPart->getId());

        if ($handler = $command->getHandler() and $handlerClass = self::NEXT_HANDLERS_COMMANDS[$handler]) {
            $this->commandBus->dispatch(
                new $handlerClass($command->getItemId(), $command->getEditor())
            );
        }
    }

    /**
     * Allow to open bid if condition has worsened
     * @param OrderItem $orderItem
     * @param OrderBid $orderBid
     * @param int $newPrice
     * @param \DateTime $newEta
     * @return bool
     */
    private function allowOpenBid(
        OrderItem $orderItem,
        OrderBid $orderBid,
        int $newPrice,
        \DateTime $newEta
    ): bool {
        $package = $orderItem->getPackage();
        $qty = $orderItem->getQty();
        $diffConsumed = $this->siteSettingService->getSettingByPath('order_management/diff_consumed');
        $diffConsumed = $diffConsumed <= 0
            ? 0
            : $this->moneyService->convert(
                $this->moneyService->getMoney($diffConsumed, MoneyService::BASE_CURRENCY),
                new Currency($package->getCurrency())
            )->getAmount();
        return ($newPrice - $diffConsumed / $qty) > $orderBid->getPrice() || $newEta > $orderBid->getDispatchDate();
    }
}
