<?php

namespace Boodmo\Sales\Model\Workflow\Bids\Handler;

use Boodmo\Sales\Model\Workflow\Bids\Command\MissAskCommand;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Sales\Entity\OrderBid;
use Boodmo\Sales\Repository\OrderBidRepository;
use Boodmo\User\Service\SupplierService;

class MissAskHandler
{
    /** @var OrderService */
    private $orderService;

    /** @var OrderBidRepository */
    private $orderBidRepository;

    /** @var SupplierService */
    private $supplierService;

    /**
     * MissAskHandler constructor.
     * @param OrderService $orderService
     * @param OrderBidRepository $orderBidRepository
     * @param SupplierService $supplierService
     */
    public function __construct(
        OrderService $orderService,
        OrderBidRepository $orderBidRepository,
        SupplierService $supplierService
    ) {
        $this->orderService = $orderService;
        $this->orderBidRepository = $orderBidRepository;
        $this->supplierService = $supplierService;
    }

    /**
     * @param MissAskCommand $command
     * @throws \Exception
     */
    public function __invoke(MissAskCommand $command): void
    {
        $supplier = $this->supplierService->loadSupplierProfile($command->getSupplierId());
        $item = $this->orderService->loadOrderItem($command->getItemId());
        $bid = new OrderBid();
        $bid->setStatus(OrderBid::STATUS_MISSED)
            ->setOrderItem($item)
            ->setSupplierProfile($supplier)
            ->setDeliveryDays(0);
        $this->orderBidRepository->save($bid);
    }
}
