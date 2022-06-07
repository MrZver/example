<?php

namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Sales\Model\Workflow\Order\Command\ShipmentReturnPackageCommand;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Service\OrderService;

class ShipmentReturnPackageHandler
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

    public function __invoke(ShipmentReturnPackageCommand $command): void
    {
        $package = $this->orderService->loadPackage($command->getPackId());
        $items   = $package->getItems()->toArray();

        $statusWorkflow = $this->orderService->getStatusWorkflow();
        $options = [
            TransitionEventInterface::CONTEXT => [
                'author' => $command->getEditor()->getEmail(),
                'action' => EventEnum::SHIPMENT_RETURN,
            ]
        ];

        $result = $statusWorkflow->raiseTransition(
            EventEnum::build(EventEnum::SHIPMENT_RETURN, $statusWorkflow->buildInputItemList($items), $options)
        );

        $this->orderService->save($package->getBundle());
        $this->orderService->triggerNotification($result);
    }
}
