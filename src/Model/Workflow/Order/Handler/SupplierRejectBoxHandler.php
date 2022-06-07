<?php

namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Sales\Entity\CancelReason;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Model\Workflow\Order\Command\SupplierRejectBoxCommand;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Shipping\Entity\ShippingBox;
use Boodmo\Shipping\Service\ShippingService;
use Doctrine\ORM\EntityManager;

final class SupplierRejectBoxHandler
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
     * SupplierRejectBoxHandler constructor.
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
     * @param SupplierRejectBoxCommand $command
     * @throws \Exception
     */
    public function __invoke(SupplierRejectBoxCommand $command): void
    {
        /* @var ShippingBox $shippingBox*/
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $statusWorkflow = $this->orderService->getStatusWorkflow();
            $options = [
                TransitionEventInterface::CONTEXT => [
                    'author' => $command->getEditor()->getEmail(),
                    'action' => EventEnum::SUPPLIER_REJECT,
                ]
            ];

            $shippingBox = $this->shippingService->loadShippingBox($command->getShippingBoxId());
            foreach ($shippingBox->getPackages() as $package) {
                $items = $package->getActiveItems()->map(function (OrderItem $item) {
                    return $item->setCancelReason(
                        $this->orderService->loadSalesCancelReason(CancelReason::SUPPLIER_NO_STOCK)
                    );
                })->toArray();

                $result = $statusWorkflow->raiseTransition(
                    EventEnum::build(EventEnum::SUPPLIER_REJECT, $statusWorkflow->buildInputItemList($items), $options)
                );

                $package->setInvoiceSnapshot([]);
                $package->setInvoiceNumber('');

                $this->orderService->save($package->getBundle());
                $this->orderService->triggerNotification($result);
            }
            $shippingBox->resetAdminValidationFlag();

            $this->entityManager->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->entityManager->getConnection()->rollBack();
            throw new \Exception(sprintf('Internal Server Error: %s', $e->getMessage()), $e->getCode(), $e);
        }
    }
}
