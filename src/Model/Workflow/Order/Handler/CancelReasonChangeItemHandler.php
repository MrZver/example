<?php

namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Sales\Model\Workflow\Order\Command\CancelReasonChangeItemCommand;
use Boodmo\Sales\Service\OrderService;

final class CancelReasonChangeItemHandler
{
    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * Constructor.
     *
     * @param OrderService        $orderService
     */
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function __invoke(CancelReasonChangeItemCommand $command): void
    {
        $item = $this->orderService->loadOrderItem($command->getItemId());
        $item->setCancelReason($this->orderService->loadSalesCancelReason($command->getReason()));
        $item->setLocked($command->getLocked() ?? false);

        $this->orderService->save($item->getPackage()->getBundle());
    }
}
