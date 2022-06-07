<?php

namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Model\Workflow\Order\Command\ApproveSupplierItemCommand;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Service\OrderService;

final class ApproveSupplierItemHandler
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

    /**
     * @param ApproveSupplierItemCommand $command
     * @throws \RuntimeException
     */
    public function __invoke(ApproveSupplierItemCommand $command): void
    {
        /** @var OrderItem $item */
        $item = $this->orderService->loadOrderItem($command->getItemId());
        $statusWorkflow = $this->orderService->getStatusWorkflow();
        $options = [
            TransitionEventInterface::CONTEXT => [
                'author' => $command->getEditor()->getEmail(),
                'action' => EventEnum::FOUND_SUPPLIER,
            ]
        ];
        $result = $statusWorkflow->raiseTransition(
            EventEnum::build(EventEnum::FOUND_SUPPLIER, $statusWorkflow->buildInputItemList([$item]), $options)
        );
        $item->resetAdminValidationFlag()
            ->setConfirmationDate($this->orderService->getDefaultConfirmationDateForItem());
        if (empty($item->getItemAcceptedBid())) {
            $bid = $item->addBid($item->createAcceptedBid());
            if (!is_null($item->getDispatchDate())) {
                $bid->setDispatchDate($item->getDispatchDate());
            }
        }

        $this->orderService->save($item->getPackage()->getBundle());
        $this->orderService->triggerNotification($result);
    }
}
