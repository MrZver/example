<?php

namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Sales\Entity\OrderBid;
use Boodmo\Sales\Model\Workflow\Order\Command\CancelApproveItemCommand;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Service\OrderService;

final class CancelApproveItemHandler
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

    public function __invoke(CancelApproveItemCommand $command): void
    {
        $item = $this->orderService->loadOrderItem($command->getItemId());
        $statusWorkflow = $this->orderService->getStatusWorkflow();

        $options = [
            TransitionEventInterface::CONTEXT => [
                'author' => $command->getEditor()->getEmail(),
                'action' => EventEnum::CUSTOMER_CANCEL_APPROVE,
            ]
        ];

        $item->setCancelReason($this->orderService->loadSalesCancelReason($command->getReason()));
        $item->setLocked($command->getLocked() ?? false);

        //TODO: Recalculate Payment totals

        $result = $statusWorkflow->raiseTransition(
            EventEnum::build(EventEnum::CUSTOMER_CANCEL_APPROVE, $statusWorkflow->buildInputItemList([$item]), $options)
        );
        foreach ($item->getBids() as $orderBid) {
            $orderBid->setStatus(OrderBid::STATUS_CANCELLED);
        }
        $item->resetFlags();
        $this->orderService->save($item->getPackage()->getBundle());
        $this->orderService->triggerNotification($result);
    }
}
