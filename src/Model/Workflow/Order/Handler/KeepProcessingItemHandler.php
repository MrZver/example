<?php

namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Sales\Model\Workflow\Order\Command\KeepProcessingItemCommand;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Service\OrderService;

final class KeepProcessingItemHandler
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

    public function __invoke(KeepProcessingItemCommand $command): void
    {
        $item = $this->orderService->loadOrderItem($command->getItemId());
        $repairStatuses = $item->getStatusHistory()[0]['from'] ?? [];
        $statusWorkflow = $this->orderService->getStatusWorkflow();
        $options = [
            TransitionEventInterface::CONTEXT => [
                'author' => $command->getEditor()->getEmail(),
                'action' => EventEnum::KEEP_PROCESSING,
            ]
        ];
        $result = $statusWorkflow->raiseTransition(
            EventEnum::build(EventEnum::KEEP_PROCESSING, $statusWorkflow->buildInputItemList([$item]), $options)
        );

        $chainEvents = [];
        switch (true) {
            case in_array(StatusEnum::SUPPLIER_NEW, $repairStatuses):
                $chainEvents[] = EventEnum::FOUND_SUPPLIER;
                break;
            case in_array(StatusEnum::CONFIRMED, $repairStatuses):
                $chainEvents[] = EventEnum::FOUND_SUPPLIER;
                $chainEvents[] = EventEnum::SUPPLIER_CONFIRM;
                break;
            case in_array(StatusEnum::READY_FOR_SHIPPING, $repairStatuses):
                $chainEvents[] = EventEnum::FOUND_SUPPLIER;
                $chainEvents[] = EventEnum::SUPPLIER_CONFIRM;
                $chainEvents[] = EventEnum::READY_FOR_DELIVERY;
                break;
            case in_array(StatusEnum::READY_FOR_SHIPPING_HUB, $repairStatuses):
                $chainEvents[] = EventEnum::FOUND_SUPPLIER;
                $chainEvents[] = EventEnum::SUPPLIER_CONFIRM;
                $chainEvents[] = EventEnum::HUB_SHIPMENT_READY;
                break;
            case in_array(StatusEnum::CANCEL_REQUESTED_SUPPLIER, $repairStatuses):
                $chainEvents[] = EventEnum::FOUND_SUPPLIER;
                $chainEvents[] = EventEnum::SUPPLIER_CANCEL_NEW;
                break;
        }
        foreach ($chainEvents as $eventName) {
            $options[TransitionEventInterface::CONTEXT]['action'] = $eventName;
            $result2 = $statusWorkflow->raiseTransition(
                EventEnum::build($eventName, $statusWorkflow->buildInputItemList([$item]), $options)
            );
            $result->merge($result2);
        }
        $item->resetAdminValidationFlag();
        $item->setCancelReason(null);
        $this->orderService->save($item->getPackage()->getBundle());
        $this->orderService->triggerNotification($result);
    }
}
