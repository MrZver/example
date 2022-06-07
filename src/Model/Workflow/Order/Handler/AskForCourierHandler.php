<?php

namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Model\Workflow\Order\Command\AskForCourierCommand;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Shipping\Entity\ShippingBox;
use Boodmo\Shipping\Service\ShippingService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

class AskForCourierHandler
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
     * PackedItemsHandler constructor.
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
     * @param AskForCourierCommand $command
     * @throws \Exception
     */
    public function __invoke(AskForCourierCommand $command): void
    {
        /* @var OrderPackage $package */
        /* @var OrderItem $orderItem */
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $package = $this->orderService->loadPackage($command->getPackageId());
            if ($package === null) {
                throw new \Exception(sprintf('Undefined order package (id: %s)', $command->getPackageId()));
            }
            $bundle = $package->getBundle();

            //new package has not ready orderItems
            if ($newPackage = $this->splitPackage($package)) {
                $bundle->addPackage($newPackage);

                $shippingBox = new ShippingBox();
                $shippingBox->setType(ShippingBox::TYPE_DIRECT);
                $shippingBox->addPackage($package);
                $this->shippingService->saveShippingBox($shippingBox);

                $this->orderService->save($bundle);
            }

            $this->entityManager->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->entityManager->getConnection()->rollBack();
            throw new \Exception(sprintf('Internal Server Error: %s', $e->getMessage()), $e->getCode(), $e);
        }
    }

    /**
     * In case of splitting will return new package with not ready orderItems.
     * @param OrderPackage $package
     * @return OrderPackage|null - new OrderPackage with not ready items
     */
    private function splitPackage(OrderPackage $package): ?OrderPackage
    {
        /* @var OrderItem $newItem */
        /* @var OrderItem $item */
        $allowSplit = false;
        $itemsWithIncorrectStatus = [];
        $newPackage = null;
        foreach ($package->getItems() as $orderItem) {
            $isReadyForShipping = $orderItem->getStatusList()->exists(
                StatusEnum::build(StatusEnum::READY_FOR_SHIPPING)
            );
            if (!$isReadyForShipping) {
                $itemsWithIncorrectStatus[] = $orderItem;
            } else {
                $allowSplit = true;
            }
        }

        //allow to split when package has ready and not ready active orderItems
        if ($allowSplit and !empty($itemsWithIncorrectStatus)) {
            $newPackage = (clone $package)->setNumber(0)->setItems(new ArrayCollection());
            if ($shippingETA = $package->getShippingETA()) {
                $newPackage->setShippingETA($package->getShippingETA());
            }
            foreach ($itemsWithIncorrectStatus as $item) {
                $package->removeItem($item);
                $newPackage->addItem($item);
            }
        }
        return $newPackage;
    }
}
