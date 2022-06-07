<?php

namespace Boodmo\Sales\Listener;

use Boodmo\Core\Model\ListenerDefinitionProviderInterface;
use Boodmo\Core\Model\ListenerDefinitionProviderTrait;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEvent;
use Boodmo\Shipping\Entity\ShippingBox;
use Boodmo\Shipping\Service\ShippingService;

class ShippingBoxDirectObserver implements ListenerDefinitionProviderInterface
{
    use ListenerDefinitionProviderTrait;

    /** @var ShippingService */
    private $shippingService;

    /**
     * ShippingBoxDirectObserver constructor.
     * @param ShippingService $shippingService
     */
    public function __construct(ShippingService $shippingService)
    {
        $this->shippingService = $shippingService;
    }

    public static function collectListeners(): void
    {
        self::addListener('*', '__invoke', -11);
    }

    /**
     * @param TransitionEvent $e
     */
    public function __invoke(TransitionEvent $e): void
    {
        $itemList = $e->getTarget();

        /** @var OrderItem $orderItem */
        foreach ($itemList as $orderItem) {
            $package = $orderItem->getPackage();
            if (!$package->getStatusList()->exists(StatusEnum::build(StatusEnum::READY_FOR_SHIPPING)) ||
                !$package->getSupplierProfile()->isDirectShipping() ||
                !is_null($package->getShippingBox())
            ) {
                continue;
            }

            $shippingBox = new ShippingBox();
            $shippingBox->setType(ShippingBox::TYPE_DIRECT);
            $shippingBox->addPackage($package);

            $this->shippingService->saveShippingBox($shippingBox);
        }
    }
}
