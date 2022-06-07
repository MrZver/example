<?php

namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Sales\Model\Workflow\Order\Command\WarehouseInItemsCommand;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Service\OrderService;

class WarehouseInItemsHandler
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

    public function __invoke(WarehouseInItemsCommand $command): void
    {
        $statusWorkflow = $this->orderService->getStatusWorkflow();
        foreach ($command->getItemsIds() as $id) {
            $orderItem = $this->orderService->loadOrderItem($id);
            $accepted  = $command->getAcceptedList()[$id];
            $package   = $orderItem->getPackage();

            if ($accepted === 0) {
                continue; //Если 0 значит с айтемом ничего не делаем
            }

            if ($orderItem->getQty() > $accepted) {
                $newOrderItem = clone $orderItem;
                $newOrderItem->setQty($orderItem->getQty() - $accepted);
                $orderItem->setQty($accepted);

                $package->addItem($newOrderItem);
            }
            $result = $statusWorkflow->raiseTransition(
                EventEnum::build(
                    EventEnum::WAREHOUSE_IN,
                    $statusWorkflow->buildInputItemList([$orderItem]),
                    [
                        TransitionEventInterface::CONTEXT => [
                            'author' => $command->getEditor()->getEmail(),
                            'action' => EventEnum::WAREHOUSE_IN,
                            'child ' => $orderItem->getId()
                        ]
                    ]
                )
            );
            $this->orderService->triggerNotification($result);
            $this->orderService->save($package->getBundle());
        }
    }
}
