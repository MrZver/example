<?php

namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Sales\Model\Workflow\Order\Command\ShipmentDispatchBoxCommand;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Shipping\Service\ShippingService;
use Doctrine\ORM\EntityManager;

final class ShipmentDispatchBoxHandler
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
     * ShipmentDispatchBoxHandler constructor.
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
     * @param ShipmentDispatchBoxCommand $command
     * @throws \Exception
     */
    public function __invoke(ShipmentDispatchBoxCommand $command): void
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $statusWorkflow = $this->orderService->getStatusWorkflow();
            $options = [
                TransitionEventInterface::CONTEXT => [
                    'author' => $command->getEditor()->getEmail(),
                    'action' => EventEnum::SHIPMENT_ACCEPT,
                ]
            ];

            $shippingBox = $this->shippingService->loadShippingBox($command->getShippingBoxId());
            foreach ($shippingBox->getPackages() as $package) {
                $items   = $package->getActiveItems()->toArray();
                $result = $statusWorkflow->raiseTransition(
                    EventEnum::build(EventEnum::SHIPMENT_ACCEPT, $statusWorkflow->buildInputItemList($items), $options)
                );

                $this->orderService->save($package->getBundle());
                $this->orderService->triggerNotification($result);
            }
            $shippingBox->setDispatchedAt(new \DateTime());
            $shippingBox->resetAdminValidationFlag();
            $this->shippingService->saveShippingBox($shippingBox);

            $this->entityManager->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->entityManager->getConnection()->rollBack();
            throw new \Exception(sprintf('Internal Server Error: %s', $e->getMessage()), $e->getCode(), $e);
        }
    }
}
