<?php

namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Sales\Entity\OrderBid;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Model\Workflow\Order\Command\SupplierConfirmItemCommand;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Service\OrderService;

final class SupplierConfirmItemHandler
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

    /**
     * @param SupplierConfirmItemCommand $command
     * @throws \Exception
     */
    public function __invoke(SupplierConfirmItemCommand $command): void
    {
        $item   = $this->orderService->loadOrderItem($command->getItemId());
        $period = $item->getPackage()->getSupplierProfile()->getDefaultDispatchDays() ?? 0;
        $statusWorkflow = $this->orderService->getStatusWorkflow();
        $options = [
            TransitionEventInterface::CONTEXT => [
                'author' => $command->getEditor()->getEmail(),
                'action' => EventEnum::SUPPLIER_CONFIRM,
            ]
        ];
        $result = $statusWorkflow->raiseTransition(
            EventEnum::build(EventEnum::SUPPLIER_CONFIRM, $statusWorkflow->buildInputItemList([$item]), $options)
        );
        $item->setDispatchDate((new \DateTime())->modify("+ $period day"));
        $item->resetAdminValidationFlag();
        $this->normalizeBids($item);
        $this->orderService->save($item->getPackage()->getBundle());
        $this->orderService->triggerNotification($result);
    }

    /**
     * Reject other bids, accept bid for current supplier
     * @param OrderItem $orderItem
     */
    private function normalizeBids(OrderItem $orderItem): void
    {
        /* @var OrderBid $bid*/
        $currentSupplierId = $orderItem->getPackage()->getSupplierProfile()->getId();
        $bid = null;
        foreach ($orderItem->getBids() as $orderBid) {
            $bidStatus = $orderBid->getStatus();
            if ($orderBid->getSupplierProfile()->getId() === $currentSupplierId) {
                if ($bid === null
                    || $orderBid->getStatus() === OrderBid::STATUS_ACCEPTED
                    || $orderBid->getUpdatedAt() > $bid->getUpdatedAt()
                ) {
                    $bid = $orderBid;
                }
            } elseif (\in_array($bidStatus, [OrderBid::STATUS_OPEN, OrderBid::STATUS_ACCEPTED], true)) {
                $orderBid->setStatus(OrderBid::STATUS_REJECTED);
            }
        }
        if ($bid === null) {
            $bid = $orderItem->createAcceptedBid();
            $orderItem->addBid($bid);
        }
        $bid->setStatus(OrderBid::STATUS_ACCEPTED);
    }
}
