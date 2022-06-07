<?php

namespace Boodmo\Sales\Listener;

use Boodmo\Catalog\Service\SupplierPartService;
use Boodmo\Core\Model\ListenerDefinitionProviderInterface;
use Boodmo\Core\Model\ListenerDefinitionProviderTrait;
use Boodmo\Sales\Entity\CancelReason;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEvent;
use Boodmo\Seo\Service\GuaService;

class ItemCancelledObserver implements ListenerDefinitionProviderInterface
{
    use ListenerDefinitionProviderTrait;

    /** @var GuaService */
    private $guaService;

    /** @var SupplierPartService */
    private $supplierPartService;

    /**
     * ItemCancelledObserver constructor.
     *
     * @param GuaService $guaService
     * @param SupplierPartService $supplierPartService
     */
    public function __construct(GuaService $guaService, SupplierPartService $supplierPartService)
    {
        $this->guaService = $guaService;
        $this->supplierPartService = $supplierPartService;
    }

    public static function collectListeners(): void
    {
        self::addListener('*', '__invoke', -9);
    }

    /**
     * @param TransitionEvent $e
     * @throws \InvalidArgumentException
     */
    public function __invoke(TransitionEvent $e): void
    {
        /** @var OrderItem $orderItem */
        if (!in_array(StatusEnum::CANCELLED, array_keys($e->getOutputRule()))) {
            return;
        }
        $itemList = $e->getTarget();
        foreach ($itemList as $orderItem) {
            if (!$orderItem->getStatusList()->exists(StatusEnum::build(StatusEnum::CANCELLED))) {
                continue;
            }
            $cancelReason = $e->getParam('cancel_reason', null) ?? $orderItem->getCancelReason();
            if ($cancelReason === null) {
                throw new \InvalidArgumentException(
                    sprintf('You should set cancellation reason for cancel item (id: %s).', $orderItem->getId()),
                    422
                );
            }
            $orderItem->setCancelReason($cancelReason);
            $orderItem->getPackage()->getBundle()->recalculateBills();
            //Disable supplier part when CR = 6
            if ($orderItem->getCancelReason()->getId() == CancelReason::SUPPLIER_NO_STOCK) {
                $this->supplierPartService->disableSupplierPart(
                    $this->supplierPartService->loadSupplierPart($orderItem->getProductId()),
                    false
                );
            }

            if (!in_array($e->getName(), [EventEnum::SPLIT_CANCEL_SUPPLIER, EventEnum::SPLIT_SUPPLIER])) {
                $bundle = $orderItem->getPackage()->getBundle();
                $this->guaService->cancelOrderItems(
                    $bundle->getGaCid(),
                    $bundle->getCustomerProfile()->getId(),
                    $bundle->getId(),
                    [['id' => $orderItem->getNumber(), 'qty' => $orderItem->getQty()]]
                );
            }
        }
    }
}
