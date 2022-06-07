<?php

namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Catalog\Service\SupplierPartService;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Model\Workflow\Order\Command\AddItemCommand;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Model\Workflow\Status\StatusList;
use Boodmo\Sales\Service\OrderService;

class AddItemHandler
{
    /** @var SupplierPartService */
    private $supplierPartService;

    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * AddItemHandler constructor.
     *
     * @param SupplierPartService $supplierPartService
     * @param OrderService        $orderService
     */
    public function __construct(
        SupplierPartService $supplierPartService,
        OrderService $orderService
    ) {
        $this->supplierPartService = $supplierPartService;
        $this->orderService = $orderService;
    }

    public function __invoke(AddItemCommand $command): void
    {
        $package = $this->orderService->loadPackage($command->getItemId());
        $newPrice = $command->getPrice();
        $newCost = $command->getCost();
        $newPartId = $command->getPartId();

        //Prepare supplier_part. get existing or create new
        $newSupplierPart = $this->supplierPartService->prepareNewSupplierPart(
            $newPartId,
            $newPrice,
            $newCost,
            $package->getCurrency(),
            $package->getSupplierProfile()
        );

        $newOrderItem = new OrderItem();
        $newOrderItem->setPackage($package);

        $part = $newSupplierPart->getPart();
        $newOrderItem = $this->orderService->updateItemPrices(
            $newOrderItem,
            $command->getPrice(),
            $command->getCost(),
            $command->getDelivery()
        )
            ->setProductId($newSupplierPart->getId())
            ->setPartId($newPartId)
            ->setNumber($part ? $part->getNumber() : $newSupplierPart->getNumber())
            ->setBrand($part ? $part->getBrand()->getName() : $newSupplierPart->getBrandCode())
            ->setFamily($part ? $part->getFamily()->getName() : '')
            ->setName($part ? $part->getName() : $newSupplierPart->getName());

        $package->addItem($newOrderItem, false);
        $newOrderItem->setDispatchDate(
            (new \DateTime())->add(
                new \DateInterval('P' . ($package->getSupplierProfile()->getDefaultDispatchDays() ?? 1) . 'D')
            )
        );
        $context = [
            'author' => $command->getEditor()->getEmail(),
            'action' => 'Add Item'
        ];
        // Todo create speliazed workflow event like technical cancel
        $newOrderItem->setStatusList(new StatusList([StatusEnum::PROCESSING, StatusEnum::CUSTOMER_NEW]), $context);

        $package->getBundle()->recalculateBills();
        $this->orderService->save($package->getBundle());
    }
}
