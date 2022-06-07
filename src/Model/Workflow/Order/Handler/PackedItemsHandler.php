<?php

namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Model\Workflow\Note\NotesMessage;
use Boodmo\Sales\Model\Workflow\Order\Command\PackedItemsCommand;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Shipping\Entity\ShippingBox;
use Boodmo\Shipping\Service\ShippingService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

class PackedItemsHandler
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
     * @param PackedItemsCommand $command
     * @throws \Exception
     */
    public function __invoke(PackedItemsCommand $command): void
    {
        /* @var OrderPackage $package */
        /* @var OrderItem $orderItem */
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $statusWorkflow = $this->orderService->getStatusWorkflow();
            $options = [
                TransitionEventInterface::CONTEXT => [
                    'author' => $command->getEditor()->getEmail(),
                    'action' => EventEnum::SHIPMENT_PACK,
                ]
            ];

            $shippingBox = new ShippingBox();
            $shippingBox->setId($command->getShippingBoxId());
            $itemsData = $command->getItems();

            foreach ($this->getPackages($command->getItemsIds(), $this->orderService) as $package) {
                $bundle = $package->getBundle();

                //new package has not ready orderItems
                if ($newPackage = $this->splitPackage($package, $itemsData)) {
                    $bundle->addPackage($newPackage);
                    $this->orderService->save($bundle);
                }

                foreach ($package->getActiveItems() as $orderItem) {
                    $orderItemId = $orderItem->getId();
                    if (isset($itemsData[$orderItemId])) {
                        $message = new NotesMessage(
                            'FULFILMENT_PACK',
                            'Time of scan: '.$itemsData[$orderItemId]['time_of_scan']
                        );
                        $orderItem->addMessageToNotes($message);
                    }

                    $result = $statusWorkflow->raiseTransition(
                        EventEnum::build(
                            EventEnum::SHIPMENT_PACK,
                            $statusWorkflow->buildInputItemList([$orderItem]),
                            $options
                        )
                    );
                    $this->orderService->save($bundle);
                    $this->orderService->triggerNotification($result);
                }

                $shippingBox->addPackage($package);
            }

            $shippingBox->setShipmentParams($command->getShipmentParams())
                ->setType(ShippingBox::TYPE_HUB)
                ->setHub($this->shippingService->loadShippingHub());
            $this->shippingService->saveShippingBox($shippingBox);

            $this->entityManager->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->entityManager->getConnection()->rollBack();
            throw new \Exception(sprintf('Internal Server Error: %s', $e->getMessage()), $e->getCode(), $e);
        }
    }

    /**
     * @param array $orderItemsIds
     * @param OrderService $orderService
     * @return array
     */
    private function getPackages(array $orderItemsIds, OrderService $orderService): array
    {
        /* @var OrderItem $item*/
        $result = [];
        foreach ($orderItemsIds as $id) {
            $orderItem = $orderService->loadOrderItem($id);
            $package = $orderItem->getPackage();
            $result[$package->getId()] = $package;
        }
        return $result;
    }

    /**
     * In case of splitting will return new package with not ready orderItems.
     * Current package with ready orderItems will be transferred in new status
     * @param OrderPackage $package
     * @param array $dataItems
     * @return OrderPackage|null - new OrderPackage with not ready items
     */
    private function splitPackage(OrderPackage $package, $dataItems): ?OrderPackage
    {
        /* @var OrderItem $newItem */
        /* @var OrderItem $item */
        $allowSplit = false;
        $itemsWithIncorrectStatus = [];
        $itemsWithIncorrectQty = [];
        $newPackage = null;
        foreach ($package->getItems() as $orderItem) {
            $isReceivedOnHub = $orderItem->getStatusList()->exists(StatusEnum::build(StatusEnum::RECEIVED_ON_HUB));
            $qtyFromItemInCommand = isset($dataItems[$orderItem->getId()])
                ? (int)$dataItems[$orderItem->getId()]['qty']
                : 0;
            if (!$isReceivedOnHub) {
                $itemsWithIncorrectStatus[] = $orderItem;
            } elseif ($isReceivedOnHub and $orderItem->getQty() !== $qtyFromItemInCommand) {
                $allowSplit = true;
                $itemsWithIncorrectQty[] = $orderItem;
            } else {
                $allowSplit = true;
            }
        }

        //allow to split when package has ready and not ready active orderItems
        if ($allowSplit and (!empty($itemsWithIncorrectStatus) or !empty($itemsWithIncorrectQty))) {
            $newPackage = (clone $package)->setNumber(0)->setItems(new ArrayCollection());
            if ($shippingBox = $package->getShippingBox()) {
                $newPackage->setShippingBox($package->getShippingBox());
            }
            if ($shippingETA = $package->getShippingETA()) {
                $newPackage->setShippingETA($package->getShippingETA());
            }
            foreach ($itemsWithIncorrectStatus as $item) {
                $package->removeItem($item);
                $newPackage->addItem($item);
            }
            foreach ($itemsWithIncorrectQty as $item) {
                if (isset($dataItems[$item->getId()]) && $dataItems[$item->getId()]['qty'] > 0) {
                    $newItem = clone $item;
                    $newItem->setQty($dataItems[$item->getId()]['qty']);

                    $item->setQty($item->getQty() - $dataItems[$item->getId()]['qty']);
                    $newPackage->addItem($newItem);
                    $newItem->createAcceptedBid();
                } else {
                    $package->removeItem($item);
                    $newPackage->addItem($item);
                }
            }
        }
        return $newPackage;
    }
}
