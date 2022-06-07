<?php


namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Sales\Model\Workflow\Order\Command\ShipmentDeniedPackageCommand;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Service\OrderService;

class ShipmentDeniedPackageHandler
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

    public function __invoke(ShipmentDeniedPackageCommand $command): void
    {
        $package = $this->orderService->loadPackage($command->getPackageId());
        $items   = $package->getItems()->toArray();
        $statusWorkflow = $this->orderService->getStatusWorkflow();
        $options = [
            TransitionEventInterface::CONTEXT => [
                'author' => $command->getEditor()->getEmail(),
                'action' => EventEnum::SHIPMENT_DENY,
            ]
        ];

        $result = $statusWorkflow->raiseTransition(
            EventEnum::build(EventEnum::SHIPMENT_DENY, $statusWorkflow->buildInputItemList($items), $options)
        );

        $package->getShippingBox()->resetAdminValidationFlag();
        $this->orderService->save($package->getBundle());
        $this->orderService->triggerNotification($result);
    }
}
