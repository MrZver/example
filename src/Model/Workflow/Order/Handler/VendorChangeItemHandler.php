<?php

namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Catalog\Entity\SupplierPart;
use Boodmo\Catalog\Service\PartService;
use Boodmo\Catalog\Service\SupplierPartService;
use Boodmo\Sales\Entity\CancelReason;
use Boodmo\Sales\Entity\OrderBid;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Model\Workflow\Order\Command\VendorChangeItemCommand;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\Status;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Model\Workflow\StatusWorkflow;
use Boodmo\Sales\Service\OrderService;
use Boodmo\User\Entity\UserProfile\Supplier;
use Boodmo\User\Service\SupplierService;
use Doctrine\Common\Collections\ArrayCollection;

final class VendorChangeItemHandler
{
    /** @var SupplierService */
    private $supplierService;

    /** @var SupplierPartService */
    private $supplierPartService;

    /** @var OrderService */
    private $orderService;

    /** @var PartService */
    private $partService;

    /**
     * VendorChangeItemHandler constructor.
     *
     * @param SupplierService $supplierService
     * @param SupplierPartService $supplierPartService
     * @param OrderService $orderService
     * @param PartService $partService
     */
    public function __construct(
        SupplierService $supplierService,
        SupplierPartService $supplierPartService,
        OrderService $orderService,
        PartService $partService
    ) {
        $this->supplierService = $supplierService;
        $this->supplierPartService = $supplierPartService;
        $this->orderService = $orderService;
        $this->partService = $partService;
    }

    /**
     * @param VendorChangeItemCommand $command
     * @throws \Exception
     */
    public function __invoke(VendorChangeItemCommand $command): void
    {
        $statusWorkflow = $this->orderService->getStatusWorkflow();
        $newSupplierId  = $command->getSupplierProfileId();
        $isNewOrId      = $command->getIsNew();
        $newPrice       = $command->getPrice();
        $newCost        = $command->getCost();
        $newDeliveryPrice = $command->getDelivery();
        $newQty         = $command->getQty();
        $bidId          = $command->getBidId();
        $authorEmail    = $command->getEditor()->getEmail();

        $orderItem          = $this->orderService->loadOrderItem($command->getItemId());
        $package            = $orderItem->getPackage();
        $bundle             = $package->getBundle();
        $packageCurrency    = $package->getCurrency();
        $newPartId          = $command->getPartId() ?? $orderItem->getPartId();
        $apiBid             = null;

        $newSupplierProfile = $this->supplierService->loadSupplierProfileByCurrency($newSupplierId, $packageCurrency);
        $oldSupplierProfile = $package->getSupplierProfile();
        if ($bidId) {
            $apiBid = $orderItem->getItemBidById($bidId);
        }

        //If action for accept (see screens -> sales)
        $this->prepareBids($bidId, $orderItem, $statusWorkflow, $authorEmail);

        //Prepare supplier_part. get existing or create new
        $newSupplierPart = $this->supplierPartService->prepareNewSupplierPart(
            $newPartId,
            $newPrice,
            $newCost,
            $packageCurrency,
            $newSupplierProfile
        );

        //Create new package or move new item to exist package
        $newOrderPackage = $this->getPackageForNewItem(
            $bundle,
            $package,
            $newSupplierProfile,
            $isNewOrId,
            $apiBid
        );

        //Prepare new item
        $newOrderItem = $this->getNewOrderItem(
            $orderItem,
            $newPrice,
            $newCost,
            $newDeliveryPrice,
            $newSupplierPart,
            $newPartId,
            $newQty,
            $newOrderPackage,
            $apiBid
        );

        //Set cancel reason for old item
        if (!$orderItem->getCancelReason()) {
            $orderItem->setCancelReason($this->orderService->loadSalesCancelReason(CancelReason::ITEM_WAS_REPLACED));
        }

        //Disable old item
        if ($command->isDisable()) {
            $this->disableOldSupplierPart($oldSupplierProfile, $orderItem->getPartId());
        }

        $result = $statusWorkflow->raiseTransition(
            EventEnum::build(
                $this->getTransitionName($orderItem),
                $statusWorkflow->buildInputItemList([$orderItem, $newOrderItem]),
                [
                    TransitionEventInterface::CONTEXT => [
                        'author' => $authorEmail,
                        'action' => 'Change Supplier',
                        'child'  => $newOrderItem->getId()
                    ]
                ]
            )
        );

        //add notes about changing cost
        $this->orderService->addNoticeAboutCost($orderItem, $newOrderItem, $command->getEditor());

        $bundle->recalculateBills();
        $this->orderService->save($bundle);
        $this->orderService->triggerNotification($result);
        //move bids from old item to new
        $this->orderService->moveBids($orderItem, $newOrderItem, $bidId === null);
        //enable part of supplier after listeners
        if ($newSupplierPart->isEnabled() === false) {
            $this->supplierPartService->enableSupplierPart($newSupplierPart);
        }
    }

    /**
     * @param OrderBundle $bundle
     * @param OrderPackage $package
     * @param Supplier $supplier
     * @param int|null $packageId
     * @param OrderBid|null $bid
     * @return OrderPackage
     * @throws \Exception
     */
    private function getPackageForNewItem(
        OrderBundle $bundle,
        OrderPackage $package,
        Supplier $supplier,
        ?int $packageId = null,
        ?OrderBid $bid
    ): OrderPackage {
        if ($packageId === null) {
            $package = clone $package;
            $pin = $bundle->getCustomerAddress()['pin'];
            $package->setDeliveryDays($this->supplierService->getDeliveryDaysByPin($pin))
                ->setSupplierProfile($supplier)
                ->setNumber(0)
                ->setCreatedAt(new \DateTime())
                ->setItems(new ArrayCollection());
            $bundle->addPackage($package);
        } else {
            $package = $this->orderService->loadPackage($packageId);
        }
        if (!is_null($bid)) {
            $package->setDeliveryDays($bid->getDeliveryDays() ?? 1);
            $package->setShippingETA(
                $bid->getDispatchDate()->add(new \DateInterval('P' . ($bid->getDeliveryDays() ?? 1) . 'D'))
            );
        }
        return $package;
    }

    private function disableOldSupplierPart(Supplier $supplierProfile, int $partId): void
    {
        $mainSupplierProfile    = $supplierProfile->getParent() ?? $supplierProfile;
        $supplierUserId         = $mainSupplierProfile->getUserInfo()->getId();

        $this->supplierPartService->disableSupplierPart(
            $this->supplierPartService->loadSupplierPartBySupplierAndPart($supplierUserId, $partId),
            false
        );
    }

    private function getNewOrderItem(
        OrderItem $orderItem,
        $price,
        $cost,
        $deliveryPrice,
        SupplierPart $supplierPart,
        $partId,
        $qty,
        OrderPackage $package,
        ?OrderBid $orderBid
    ): OrderItem {
        $part = null;
        if ($orderBid) {
            $sku  = $this->partService->normalizeSku($orderBid->getBrand(), $orderBid->getNumber());
            $part = $this->partService->loadPartBySku($sku);
        }
        if (!$part) {
            $part = $supplierPart->getPart();
        }
        $newOrderItem = (clone $orderItem)->setPackage($orderItem->getPackage());
        $newOrderItem = $this->orderService->updateItemPrices($newOrderItem, $price, $cost, $deliveryPrice);
        $newOrderItem->setProductId($supplierPart->getId())
            ->setPartId($part->getId())
            ->setNumber($part->getNumber())
            ->setQty($qty)
            ->setBrand($part->getBrand()->getName())
            ->setFamily($part->getFamily()->getName())
            ->setCancelReason(null)
            ->setName($part->getName())
            ->resetAdminValidationFlag()
            ->setConfirmationDate($this->orderService->getDefaultConfirmationDateForItem());
        //add item to package
        $package->addItem($newOrderItem);
        //Set new dispatch date for new item
        if ($orderBid) { //For accept bid
            $newOrderItem->setDispatchDate($orderBid->getDispatchDate());
        } else {
            $newOrderItem->setDispatchDate( //For vendor change
                (new \DateTime())->add(
                    new \DateInterval('P' . ($package->getSupplierProfile()->getDefaultDispatchDays() ?? 1) . 'D')
                )
            );
        }
        return $newOrderItem;
    }

    private function getTransitionName(OrderItem $orderItem): string
    {
        return $orderItem->getStatusList()->exists(StatusEnum::build(StatusEnum::PROCESSING))
            ? EventEnum::SPLIT_SUPPLIER
            : EventEnum::SPLIT_CANCEL_SUPPLIER;
    }

    /**
     * @param null|string $bidId
     * @param OrderItem $orderItem
     * @param StatusWorkflow $statusWorkflow
     * @param string $authorEmail
     * @throws \Exception
     */
    private function prepareBids(
        ?string $bidId,
        OrderItem $orderItem,
        StatusWorkflow $statusWorkflow,
        string $authorEmail
    ): void {
        if ($bidId !== null) {
            $supplierStatus = $orderItem->getStatusList()->fallbackStatus(Status::TYPE_SUPPLIER)->getCode();
            if ($supplierStatus === StatusEnum::SUPPLIER_NEW) {
                $event = EventEnum::SUPPLIER_CANCEL_NEW;
            } elseif ($supplierStatus === StatusEnum::CONFIRMED) {
                $event = EventEnum::SUPPLIER_CANCEL_CONFIRMED;
            } else {
                $event = null;
            }

            if (!is_null($event)) {
                $statusWorkflow->raiseTransition(
                    EventEnum::build(
                        $event,
                        $statusWorkflow->buildInputItemList([$orderItem]),
                        [
                            TransitionEventInterface::CONTEXT => [
                                'author' => $authorEmail,
                                'action' => $event
                            ]
                        ]
                    )
                );
            }
            foreach ($orderItem->getBids() as $bid) {
                $status = $bid->getStatus();
                if ($status === OrderBid::STATUS_OPEN || $status === OrderBid::STATUS_ACCEPTED) {
                    $bid->setStatus(OrderBid::STATUS_REJECTED);
                }
                if ($bid->getId() === $bidId) {
                    $bid->setStatus(OrderBid::STATUS_ACCEPTED);
                }
            }
        }
    }
}
