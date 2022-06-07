<?php

namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Catalog\Service\SupplierPartService;
use Boodmo\Sales\Entity\CancelReason;
use Boodmo\Sales\Model\Workflow\Order\Command\EditItemCommand;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Service\OrderService;

final class EditItemHandler
{
    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var SupplierPartService
     */
    private $supplierPartService;

    /**
     * Constructor.
     *
     * @param OrderService        $orderService
     * @param SupplierPartService $supplierPartService
     */
    public function __construct(
        OrderService $orderService,
        SupplierPartService $supplierPartService
    ) {
        $this->orderService = $orderService;
        $this->supplierPartService = $supplierPartService;
    }

    /**
     * @param EditItemCommand $command
     * @throws \Exception
     */
    public function __invoke(EditItemCommand $command): void
    {
        $orderItem = $this->orderService->loadOrderItem($command->getItemId());
        $newOrderItem = clone $orderItem;
        $newOrderItem->setPackage($orderItem->getPackage());

        $newPrice = $command->getPrice();
        $newCost = $command->getCost();

        //Prepare supplier_part. get existing or create new
        $newSupplierPart = $this->supplierPartService->prepareNewSupplierPart(
            $orderItem->getPartId(),
            $newPrice,
            $newCost,
            $orderItem->getPackage()->getCurrency(),
            $orderItem->getPackage()->getSupplierProfile()
        );

        $newOrderItem = $this->orderService->updateItemPrices(
            $newOrderItem,
            $newPrice,
            $newCost,
            $command->getDelivery()
        )
            ->setQty($command->getQty())
            ->setProductId($newSupplierPart->getId())
            ->setNumber($newSupplierPart->getPart()->getNumber())
            ->setBrand($newSupplierPart->getPart()->getBrand()->getName())
            ->setFamily($newSupplierPart->getPart()->getFamily()->getName())
            ->setName($newSupplierPart->getPart()->getName());

        $orderItem->getPackage()->addItem($newOrderItem);

        $statusWorkflow = $this->orderService->getStatusWorkflow();
        $result = $statusWorkflow->raiseTransition(
            EventEnum::build(
                EventEnum::TECHNICAL_CANCEL,
                $statusWorkflow->buildInputItemList([$orderItem]),
                [
                    TransitionEventInterface::CONTEXT => [
                        'author' => $command->getEditor()->getEmail(),
                        'action' => 'Edit Item',
                        'child'  => $newOrderItem->getId(),
                    ],
                    'cancel_reason' => $this->orderService->loadSalesCancelReason(CancelReason::ITEM_WAS_REPLACED)
                ]
            )
        );

        //add notes about changing cost
        $this->orderService->addNoticeAboutCost($orderItem, $newOrderItem, $command->getEditor());

        // todo Узнать у Андрея про notes
        $this->orderService->save($orderItem->getPackage()->getBundle());
        $this->orderService->triggerNotification($result);
        //move bids from old item to new
        $this->orderService->moveBids($orderItem, $newOrderItem);
    }
}
