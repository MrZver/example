<?php

namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Sales\Model\Workflow\Order\Command\ShipmentDeliveryBoxCommand;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Shipping\Entity\ShippingBox;
use Boodmo\Shipping\Service\ShippingService;
use Doctrine\ORM\EntityManager;

final class ShipmentDeliveryBoxHandler
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
     * ShipmentDeliveryBoxHandler constructor.
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
     * @param ShipmentDeliveryBoxCommand $command
     * @throws \Exception
     */
    public function __invoke(ShipmentDeliveryBoxCommand $command): void
    {
        /* @var ShippingBox $shippingBox*/

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $statusWorkflow = $this->orderService->getStatusWorkflow();
            $options = [
                TransitionEventInterface::CONTEXT => [
                    'author' => $command->getEditor()->getEmail(),
                    'action' => EventEnum::SHIPMENT_RECEIVED,
                ]
            ];

            $shippingBox = $this->shippingService->loadShippingBox($command->getShippingBoxId());
            foreach ($shippingBox->getPackages() as $package) {
                $package->resetFlags();
                foreach ($package->getActiveItems() as $item) {
                    $item->resetFlags();
                }
                $items   = $package->getActiveItems()->toArray();
                $result = $statusWorkflow->raiseTransition(
                    EventEnum::build(
                        EventEnum::SHIPMENT_RECEIVED,
                        $statusWorkflow->buildInputItemList($items),
                        $options
                    )
                );

                $package->setDeliveredAt(new \DateTime());
                $this->orderService->save($package->getBundle());
                $this->orderService->triggerNotification($result);
            }
            $shippingBox->setDeliveredAt(new \DateTime());
            $shippingBox->resetFlags();
            $this->shippingService->saveShippingBox($shippingBox);

            $this->entityManager->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->entityManager->getConnection()->rollBack();
            throw new \Exception(sprintf('Internal Server Error: %s', $e->getMessage()), $e->getCode(), $e);
        }
    }
}
