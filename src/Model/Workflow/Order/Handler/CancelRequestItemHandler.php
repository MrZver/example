<?php

namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Model\Workflow\Order\Command\CancelRequestItemCommand;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\Status;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Service\OrderService;

final class CancelRequestItemHandler
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

    public function __invoke(CancelRequestItemCommand $command): void
    {
        /**
         * @var OrderItem $item
         * @var OrderPackage $package
         */
        $item = $this->orderService->loadOrderItem($command->getItemId());
        $statusWorkflow = $this->orderService->getStatusWorkflow();
        $eventCode = $this->getEventCode($item, $command->isCustomer());

        $options = [
            TransitionEventInterface::CONTEXT => [
                'author' => $command->getEditor()->getEmail(),
                'action' => ($eventCode === EventEnum::CANCEL_SUPPLIER_USER) ? "CANCEL_BY_USER" : $eventCode,
            ]
        ];
        $item->setCancelReason($this->orderService->loadSalesCancelReason($command->getReason()));
        $result = $statusWorkflow->raiseTransition(
            EventEnum::build($eventCode, $statusWorkflow->buildInputItemList([$item]), $options)
        );
        $item->resetAdminValidationFlag();
        $this->orderService->save($item->getPackage()->getBundle());
        $this->orderService->triggerNotification($result);
    }

    /**
     * @param OrderItem $item
     * @param bool $isCustomer
     * @return null|string
     * @throws \RuntimeException
     */
    private function getEventCode(OrderItem $item, bool $isCustomer): ?string
    {
        $status = $item->getStatusList()->fallbackStatus(Status::TYPE_SUPPLIER);
        switch ($status->getCode()) {
            case StatusEnum::SUPPLIER_NEW:
                $event = EventEnum::CANCEL_DROPSHIPPED_USER;
                break;
            case StatusEnum::CONFIRMED:
                $event = EventEnum::CANCEL_CONFIRMED_USER;
                break;
            case StatusEnum::READY_FOR_SHIPPING:
                $event = EventEnum::CANCEL_SHIPPING_USER;
                break;
            case StatusEnum::READY_FOR_SHIPPING_HUB:
                $event = EventEnum::CANCEL_HUB_USER;
                break;
            case StatusEnum::PROCESSING:
                $event = $isCustomer && $this->isUnpaidItem($item)
                    ? EventEnum::CANCEL_NOT_PAID
                    : EventEnum::CANCEL_PROCESSING_USER;
                break;
            case StatusEnum::CANCEL_REQUESTED_SUPPLIER:
                $event = EventEnum::CANCEL_SUPPLIER_USER;
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
        return $event;
    }

    /**
     * Is item unpaid?
     * @param OrderItem $item
     * @return bool
     */
    private function isUnpaidItem(OrderItem $item) : bool
    {
        $result = false;
        $package = $item->getPackage();
        $packageCurrency = $package->getCurrency();
        $paidMoney = $package->getBundle()->getPaymentsAppliedMoney();
        if (!isset($paidMoney[$packageCurrency]) || $paidMoney[$packageCurrency]->isZero()) {
            $result = true;
        }
        return $result;
    }
}
