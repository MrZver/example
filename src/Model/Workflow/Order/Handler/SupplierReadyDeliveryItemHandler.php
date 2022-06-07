<?php

namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Sales\Model\Workflow\Order\Command\SupplierReadyDeliveryItemCommand;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Service\OrderService;

final class SupplierReadyDeliveryItemHandler
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

    public function __invoke(SupplierReadyDeliveryItemCommand $command): void
    {
        $item = $this->orderService->loadOrderItem($command->getItemId());
        $statusWorkflow = $this->orderService->getStatusWorkflow();
        $options = [
            TransitionEventInterface::CONTEXT => [
                'author' => $command->getEditor()->getEmail(),
                'action' => EventEnum::READY_FOR_DELIVERY,
            ]
        ];
        $result = $statusWorkflow->raiseTransition(
            EventEnum::build(EventEnum::READY_FOR_DELIVERY, $statusWorkflow->buildInputItemList([$item]), $options)
        );
        $item->resetAdminValidationFlag();
        $this->orderService->save($item->getPackage()->getBundle());
        $this->orderService->triggerNotification($result);
    }
}
