<?php

namespace Boodmo\Sales\Hydrator;

use Boodmo\Core\Hydrator\BaseHydrator;
use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Model\Workflow\Status\Status;
use Money\Currency;
use Money\Money;

class OrderItemHydrator extends BaseHydrator
{
    /** @var MoneyService */
    private $moneyService;

    /** @var OrderRmaHydrator */
    private $orderRmaHydrator;

    /** @var CancelReasonHydrator */
    private $cancelReasonHydrator;

    public function __construct(MoneyService $moneyService)
    {
        parent::__construct();
        $this->moneyService = $moneyService;
        $this->orderRmaHydrator = new OrderRmaHydrator();
        $this->cancelReasonHydrator = new CancelReasonHydrator();
    }

    /**
     * @param OrderItem $entity
     * @param string $currency
     * @return array
     */
    public function extract($entity, $currency = null): array
    {
        $packageCurrency = $entity->getPackage()->getCurrency();
        $toCurrency = $currency ?? $packageCurrency;
        $round = $toCurrency === MoneyService::BASE_CURRENCY && $toCurrency !== $packageCurrency
            ? Money::ROUND_UP
            : null;
        foreach ($entity->getRmaList() as $orderRma) {
            $returns[] = $this->orderRmaHydrator->extract($orderRma);
        }

        $custom = [
            'price' => $this->moneyService->convert(
                $this->moneyService->getMoney($entity->getPrice() / 100, $packageCurrency),
                new Currency($toCurrency),
                $round
            ),
            'delivery_price' => $this->moneyService->convert(
                $this->moneyService->getMoney($entity->getDeliveryPrice() / 100, $packageCurrency),
                new Currency($toCurrency),
                $round
            ),
            'sub_total' => $this->moneyService->convert(
                $this->moneyService->getMoney($entity->getSubTotal() / 100, $packageCurrency),
                new Currency($toCurrency),
                $round
            ),
            'delivery_total' => $this->moneyService->convert(
                $this->moneyService->getMoney($entity->getDeliveryTotal() / 100, $packageCurrency),
                new Currency($toCurrency),
                $round
            ),
            'origin_price' => $this->moneyService->convert(
                $this->moneyService->getMoney($entity->getOriginPrice() / 100, $packageCurrency),
                new Currency($toCurrency),
                $round
            ),
            'cost' => $this->moneyService->convert(
                $this->moneyService->getMoney($entity->getCost() / 100, $packageCurrency),
                new Currency($toCurrency),
                $round
            ),
            'cost_total' => $this->moneyService->convert(
                $this->moneyService->getMoney($entity->getCostTotal() / 100, $packageCurrency),
                new Currency($toCurrency),
                $round
            ),
            'grand_total' => $this->moneyService->convert(
                $this->moneyService->getMoney($entity->getGrandTotal() / 100, $packageCurrency),
                new Currency($toCurrency),
                $round
            ),
            'base_sub_total' => $this->moneyService->getMoney(
                $entity->getBaseSubTotal() / 100,
                MoneyService::BASE_CURRENCY
            ),
            'base_price' => $this->moneyService->getMoney($entity->getBasePrice() / 100, MoneyService::BASE_CURRENCY),
            'base_cost' => $this->moneyService->getMoney($entity->getBaseCost() / 100, MoneyService::BASE_CURRENCY),
            'base_origin_price' => $this->moneyService->getMoney(
                $entity->getBaseOriginPrice() / 100,
                MoneyService::BASE_CURRENCY
            ),
            'base_delivery_price' => $this->moneyService->getMoney(
                $entity->getBaseDeliveryPrice() / 100,
                MoneyService::BASE_CURRENCY
            ),
            'customer_status' => $entity->getStatusList()->fallbackStatus(Status::TYPE_CUSTOMER)->getName(),
            'rma_list' => $returns ?? [],
            'cancel_reason' => $entity->getCancelReason() ? $this->cancelReasonHydrator->extract($entity->getCancelReason()) : null
        ];

        return array_merge($this->classMethods->extract($entity), $custom);
    }
}
