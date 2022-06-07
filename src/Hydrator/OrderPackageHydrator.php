<?php


namespace Boodmo\Sales\Hydrator;

use Boodmo\Core\Hydrator\BaseHydrator;
use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Entity\CancelReason;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Model\Workflow\Status\Status;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Shipping\Hydrator\ShippingBoxHydrator;
use Boodmo\Shipping\Service\ShippingService;
use Boodmo\User\Hydrator\SupplierHydrator;

class OrderPackageHydrator extends BaseHydrator
{
    /** @var OrderItemHydrator  */
    private $orderItemHydrator;

    /** @var SupplierHydrator  */
    private $supplierHydrator;

    /** @var ShippingService  */
    private $shippingService;

    /** @var MoneyService */
    private $moneyService;

    public function __construct(
        MoneyService $moneyService,
        OrderItemHydrator $orderItemHydrator,
        SupplierHydrator $supplierHydrator,
        ShippingService $shippingService
    ) {
        parent::__construct();
        $this->moneyService = $moneyService;
        $this->orderItemHydrator = $orderItemHydrator;
        $this->supplierHydrator = $supplierHydrator;
        $this->shippingService = $shippingService;
    }

    /**
     * @param OrderPackage $entity
     * @param string $currency
     * @return array
     */
    public function extract($entity, $currency = null): array
    {
        $currency = $currency ?? $entity->getCurrency();
        $items = [];
        foreach ($entity->getItems() as $item) {
            if ($this->getMode() === self::MODE_MOBILE) {
                //do not show replaced items
                if (!$item->isReplaced()) {
                    $items[] = $this->orderItemHydrator->setMode($this->getMode())->extract($item, $currency);
                }
            } else {
                $items[] = $this->orderItemHydrator->setMode($this->getMode())->extract($item, $currency);
            }
        }
        if ($this->getMode() === self::MODE_MOBILE) {
            if (empty($items)) {
                return [];
            }
        }

        $orderPackage = $this->classMethods->extract($entity);
        $isActiveBaseCurrency = $currency === MoneyService::BASE_CURRENCY;
        $custom = [
            'items'        => $items ?? [],
            'supplier'     => $this->supplierHydrator->extract($entity->getSupplierProfile()),
            'carrier'      => isset($orderPackage['shipping_method'])
                ? $this->shippingService->getCarrierByCode($orderPackage['shipping_method'])->getCarrierName()
                : null,
            'provider'     => isset($orderPackage['shipping_method'])
                ? $this->shippingService->getProviderByCode($orderPackage['shipping_method'])->getProviderName()
                : null,
            'shipping_eta' => $entity->getShippingETA() ? $entity->getShippingETA()->format('Y-m-d') : null,
            'grand_total' => $isActiveBaseCurrency
                ? $this->getMoney($entity->getBaseGrandTotal())
                : $this->getMoney($entity->getGrandTotal(), $entity->getCurrency()),
            'base_grand_total' => $this->getMoney($entity->getBaseGrandTotal()),
            'cost_total' => $isActiveBaseCurrency
                ? $this->getMoney($entity->getBaseCostTotal())
                : $this->getMoney($entity->getCostTotal(), $entity->getCurrency()),
            'base_cost_total' => $this->getMoney($entity->getBaseCostTotal()),
            'delivery_total' => $isActiveBaseCurrency ?
                $this->getMoney($entity->getBaseDeliveryTotal())
                : $this->getMoney($entity->getDeliveryTotal(), $entity->getCurrency()),
            'base_delivery_total' => $this->getMoney($entity->getBaseDeliveryTotal()),
            'sub_total' => $isActiveBaseCurrency ?
                $this->getMoney($entity->getBaseSubTotal())
                : $this->getMoney($entity->getSubTotal(), $entity->getCurrency()),
            'base_sub_total' => $this->getMoney($entity->getBaseSubTotal()),
            'customer_status' => $entity->getStatusList()->fallbackStatus(Status::TYPE_CUSTOMER)->getName(),
            'order_bundle_id' => $entity->getBundle() ? $entity->getBundle()->getId() : null,
            'shipping_box'    => $this->shippingService->loadPackageShippingBox($entity->getId())
        ];

        if ($this->getMode() === self::MODE_MOBILE) {
            $shippingBox = $entity->getShippingBox();
            $custom['carrier'] = ($shippingBox and $shipCode = $shippingBox->getMethod())
                ? $this->shippingService->getCarrierByCode($shipCode)->getCarrierName()
                : null;

            $trackData = OrderService::getTrackPackageData($entity);
            $trackData[1]['name'] = 'Placed on';
            $trackData[2]['name'] = 'Processing';
            $trackData[3]['name'] = 'Ready to Send';
            $trackData[4]['name'] = $trackData[4]['status'] ? 'Dispatched' : 'Expected dispatch date';
            $trackData[5]['name'] = $trackData[5]['status'] ? 'Delivered' : 'Expected delivery date';
            $trackData[6]['name'] = 'Cancelled';
            $custom['track_data'] = $trackData;
        }

        return array_merge($orderPackage, $custom);
    }

    private function getMoney($price, $currency = MoneyService::BASE_CURRENCY)
    {
        return $this->moneyService->getMoney($price / 100, $currency);
    }
}
