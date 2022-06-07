<?php

namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Sales\Model\Workflow\Order\Command\SnoozeConfirmDateItemCommand;
use Boodmo\Sales\Service\OrderService;

final class SnoozeConfirmDateItemHandler
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

    public function __invoke(SnoozeConfirmDateItemCommand $command): void
    {
        $orderItem = $this->orderService->loadOrderItem($command->getItemId());
//        $confirmationDate = $orderItem->getConfirmationDate() ?? new \DateTimeImmutable('now');
        $confirmationDate = new \DateTimeImmutable($command->getConfirmationDate());
        $orderItem->setConfirmationDate($confirmationDate);
//        $orderItem->setConfirmationDate($confirmationDate->add(new \DateInterval('P'.$command->getDaysCount().'D')));
        $this->orderService->save($orderItem->getPackage()->getBundle());
    }
}
