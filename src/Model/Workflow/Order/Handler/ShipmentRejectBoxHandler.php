<?php

namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Sales\Entity\CancelReason;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Model\Workflow\Order\Command\ShipmentRejectBoxCommand;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Shipping\Service\ShippingService;
use Doctrine\ORM\EntityManager;

final class ShipmentRejectBoxHandler
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ShippingService
     */
    private $shippingService;
    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * ShipmentRejectBoxHandler constructor.
     *
     * @param EntityManager   $entityManager
     * @param ShippingService $shippingService
     * @param OrderService    $orderService
     */
    public function __construct(
        EntityManager $entityManager,
        ShippingService $shippingService,
        OrderService $orderService
    ) {
        $this->entityManager = $entityManager;
        $this->shippingService = $shippingService;
        $this->orderService = $orderService;
    }

    /**
     * @param ShipmentRejectBoxCommand $command
     * @throws \Exception
     */
    public function __invoke(ShipmentRejectBoxCommand $command): void
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $statusWorkflow = $this->orderService->getStatusWorkflow();
            $options = [
                TransitionEventInterface::CONTEXT => [
                    'author' => $command->getEditor()->getEmail(),
                    'action' => EventEnum::SHIPMENT_REJECT,
                ]
            ];

            $shippingBox = $this->shippingService->loadShippingBox($command->getShippingBoxId());
            foreach ($shippingBox->getPackages() as $package) {
                $items = $package->getActiveItems()->map(function (OrderItem $item) use ($command) {
                    return $item->setCancelReason($this->orderService->loadSalesCancelReason($command->getReason()));
                })->toArray();

                $result = $statusWorkflow->raiseTransition(
                    EventEnum::build(EventEnum::SHIPMENT_REJECT, $statusWorkflow->buildInputItemList($items), $options)
                );

                $shippingBox->resetAdminValidationFlag();
                $this->orderService->save($package->getBundle());
                $this->orderService->triggerNotification($result);
            }

            $this->entityManager->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->entityManager->getConnection()->rollBack();
            throw new \Exception(sprintf('Internal Server Error: %s', $e->getMessage()), $e->getCode(), $e);
        }
    }
}
