<?php

namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Catalog\Service\PartService;
use Boodmo\Catalog\Service\SupplierPartService;
use Boodmo\Sales\Entity\CancelReason;
use Boodmo\Sales\Model\Workflow\Order\Command\ReplaceItemCommand;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Service\OrderService;

final class ReplaceItemHandler
{
    /** @var SupplierPartService */
    private $supplierPartService;

    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var PartService
     */
    private $partService;

    /**
     * ReplaceItemHandler constructor.
     *
     * @param SupplierPartService $supplierPartService
     * @param OrderService        $orderService
     * @param PartService         $partService
     */
    public function __construct(
        SupplierPartService $supplierPartService,
        OrderService $orderService,
        PartService $partService
    ) {
        $this->supplierPartService = $supplierPartService;
        $this->orderService = $orderService;
        $this->partService = $partService;
    }

    /**
     * @param ReplaceItemCommand $command
     * @throws \Exception
     */
    public function __invoke(ReplaceItemCommand $command): void
    {

        $orderItem = $this->orderService->loadOrderItem($command->getItemId());
        $package   = $orderItem->getPackage();
        $newOrderItem = clone $orderItem;
        $newOrderItem->setPackage($orderItem->getPackage());

        $newPrice   = $command->getPrice();
        $newCost    = $command->getCost();
        $newPartId  = $command->getPartId() ?? $orderItem->getPartId();

        //Prepare supplier_part. get existing or create new
        $newSupplierPart = $this->supplierPartService->prepareNewSupplierPart(
            $newPartId,
            $newPrice,
            $newCost,
            $package->getCurrency(),
            $package->getSupplierProfile()
        );

        $newOrderItem = $this->orderService->updateItemPrices(
            $newOrderItem,
            $newPrice,
            $newCost,
            $command->getDelivery()
        )
            ->setProductId($newSupplierPart->getId())
            ->setPartId($newPartId)
            ->setNumber($newSupplierPart->getPart()->getNumber())
            ->setQty($command->getQty())
            ->setBrand($newSupplierPart->getPart()->getBrand()->getName())
            ->setFamily($newSupplierPart->getPart()->getFamily()->getName())
            ->setName($newSupplierPart->getPart()->getName());

        $package->addItem($newOrderItem);
        if ($command->isUpdateDispatch()) {
            $newOrderItem->setDispatchDate(
                (new \DateTime())->add(
                    new \DateInterval('P' . ($package->getSupplierProfile()->getDefaultDispatchDays() ?? 1) . 'D')
                )
            );
        }
        $orderItem->setCancelReason($this->orderService->loadSalesCancelReason(CancelReason::ITEM_WAS_REPLACED));
        $statusWorkflow = $this->orderService->getStatusWorkflow();
        $result = $statusWorkflow->raiseTransition(
            EventEnum::build(
                EventEnum::TECHNICAL_CANCEL,
                $statusWorkflow->buildInputItemList([$orderItem]),
                [
                    TransitionEventInterface::CONTEXT => [
                        'author' => $command->getEditor()->getEmail(),
                        'action' => 'Replace Item',
                        'child'  => $newOrderItem->getId()
                    ],
                ]
            )
        );

        //add notes about changing cost
        $this->orderService->addNoticeAboutCost($orderItem, $newOrderItem, $command->getEditor());

        if ($command->isReplacementMode() and $part = $this->partService->loadPart($orderItem->getPartId())) {
            $part->addReplacementPart($newSupplierPart->getPart());
            $this->partService->save($part);
        }

        $newOrderItem->resetAdminValidationFlag();
        $this->orderService->save($package->getBundle());
        $this->orderService->triggerNotification($result);
        //move bids from old item to new
        $this->orderService->moveBids($orderItem, $newOrderItem);
    }
}
