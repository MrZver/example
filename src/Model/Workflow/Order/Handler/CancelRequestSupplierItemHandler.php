<?php

namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Catalog\Service\SupplierPartService;
use Boodmo\Sales\Entity\CancelReason;
use Boodmo\Sales\Model\Workflow\Order\Command\CancelRequestSupplierItemCommand;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\Status;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Sales\Repository\OrderBidRepository;
use Boodmo\Sales\Entity\OrderBid;

final class CancelRequestSupplierItemHandler
{
    /**
     * @var SupplierPartService
     */
    private $supplierPartService;
    /**
     * @var OrderService
     */
    private $orderService;
    /**
     * @var OrderBidRepository
     */
    private $orderBidRepository;

    public function __construct(SupplierPartService $supplierPartService, OrderService $orderService, OrderBidRepository $orderBidRepository)
    {
        $this->supplierPartService = $supplierPartService;
        $this->orderService = $orderService;
        $this->orderBidRepository = $orderBidRepository;
    }

    /**
     * @param CancelRequestSupplierItemCommand $command
     * @throws \RuntimeException|\Exception
     */
    public function __invoke(CancelRequestSupplierItemCommand $command): void
    {
        $item = $this->orderService->loadOrderItem($command->getItemId());
        $statusWorkflow = $this->orderService->getStatusWorkflow();

        $status = $item->getStatusList()->fallbackStatus(Status::TYPE_SUPPLIER);
        switch ($status->getCode()) {
            case StatusEnum::SUPPLIER_NEW:
                $event = EventEnum::SUPPLIER_CANCEL_NEW;
                break;
            case StatusEnum::CONFIRMED:
                $event = EventEnum::SUPPLIER_CANCEL_CONFIRMED;
                break;
            case StatusEnum::READY_FOR_SHIPPING:
                $event = EventEnum::SUPPLIER_REFUSE;
                break;
            case StatusEnum::READY_FOR_SHIPPING_HUB:
                $event = EventEnum::SUPPLIER_REFUSE_HUB;
                break;
            default:
                throw new \RuntimeException(
                    sprintf(
                        'From current status (%s) can not trigger cancel command (item id: %s)',
                        $status->getCode(),
                        $item->getId()
                    ),
                    422
                );
                break;
        }

        $options = [
            TransitionEventInterface::CONTEXT => [
                'author' => $command->getEditor()->getEmail(),
                'action' => $event,
            ]
        ];

        $item->setCancelReason($this->orderService->loadSalesCancelReason(CancelReason::SUPPLIER_NO_STOCK));

        $result = $statusWorkflow->raiseTransition(
            EventEnum::build($event, $statusWorkflow->buildInputItemList([$item]), $options)
        );

        $this->supplierPartService->disableSupplierPart(
            $this->supplierPartService->loadSupplierPart($item->getProductId())
        );
        $item->resetAdminValidationFlag();

        $supplier = $item->getPackage()->getSupplierProfile();
        $bids = $item->getBids()->filter(function (OrderBid $bid) use ($supplier) {
            return $supplier->getId() == $bid->getSupplierProfile()->getId();
        });
        foreach ($bids as $bid) {
            /* @var $bid OrderBid */
            $bid->setStatus(OrderBid::STATUS_CANCELLED);
            $this->orderBidRepository->save($bid);
        }

        $this->orderService->save($item->getPackage()->getBundle());
        $this->orderService->triggerNotification($result);
    }
}
