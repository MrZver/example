<?php

namespace Boodmo\Sales\Model;

use Boodmo\Currency\Service\MoneyService;
use Boodmo\Shipping\Entity\Logistics;
use Boodmo\Shipping\Entity\ShippingCalculation;
use Boodmo\Shipping\Model\Location;
use DateTime;
use Money\Currency;
use Money\Money;

class Delivery
{
    /**
     * @var Product
     */
    private $product;
    private $days;
    private $price;
    private $basePrice;
    /**
     * @var DeliveryBuilder
     */
    private $builder;

    /**
     * @var Logistics
     */
    private $logistic;

    /**
     * Delivery constructor.
     *
     * @param DeliveryBuilder $builder
     * @param Product $product
     */
    public function __construct(DeliveryBuilder $builder, Product $product)
    {
        $this->builder = $builder;
        $this->product = $product;
    }

    private function modificationPrice(int $price, ShippingCalculation $calculation, int $qty): int
    {
        $qtyMulti = $qty;
        foreach ($calculation->getQuantityMultiplicator() as $key => $value) {
            $range = explode("-", $key);
            if ($qty >= (int)$range[0] && $qty <= (int)$range[1]) {
                $qtyMulti = (float) $value;
            }
        }
        return (int) round((($price * $calculation->getTaxModifier() + $calculation->getSizeModifier()*100) * $qtyMulti)
            / ((int)$qty));
    }

    private function calculatePrice(): void
    {
        $logistic = $this->getLogistic();
        $price = $this->modificationPrice(
            $logistic->getPrice() * 100,
            $this->builder->getDeliveryFinder()->findShippingCalculation($this->product->getFamilyId()),
            $this->product->getRequestedQty()
        );
        $converter = $this->product->builder->getConverter();
        $productCurrency = $this->product->getPrice()->getCurrency();
        $priceCurrency = new Currency(MoneyService::BASE_CURRENCY);
        $priceMoney = new Money($price, $priceCurrency);

        $this->price = $this->basePrice = $converter->convert($priceMoney, $priceCurrency, Money::ROUND_UP);

        if (!$productCurrency->equals($priceCurrency)) {
            $this->price = $converter->convert($priceMoney, $productCurrency);
        }
    }

    public function fromLocation(): Location
    {
        return $this->product->getSeller()->getLocation();
    }

    public function toLocation(): Location
    {
        return $this->builder->getLocation()->isUnknown()
            ? $this->builder->getDefaultLocation()
            : $this->builder->getLocation();
    }

    public function getLogistic(): Logistics
    {
        if (is_null($this->logistic)) {
            $this->logistic = $this->builder->getDeliveryFinder()->findLogistic(
                $this->fromLocation(),
                $this->toLocation()
            );
        }
        return $this->logistic;
    }

    public function getPrice(): Money
    {
        if (is_null($this->price)) {
            $this->calculatePrice();
        }
        return $this->price;
    }

    public function getBasePrice(): Money
    {
        if (is_null($this->basePrice)) {
            $this->calculatePrice();
        }
        return $this->basePrice;
    }

    public function getDays(): int
    {
        if (is_null($this->days)) {
            $this->days = $this->getLogistic()->getDays();
        }
        return $this->days;
    }

    public function getTotalDays(bool $allowIgnoreAnyLogisticLocation = false): int
    {
        $logisticDays = $this->getDays();
        if ($allowIgnoreAnyLogisticLocation && $this->logistic->isAnyToCity() && $this->logistic->isAnyToState()) {
            $logisticDays = 0;
        }
        return $logisticDays + $this->product->getSeller()->getDispatchDays();
    }

    public function getShippingETA(): DateTime
    {
        return (new DateTime())->add(
            new \DateInterval('P' . $this->getTotalDays() . 'D')
        );
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'shipping_eta' => $this->getShippingETA()->getTimestamp(),
            'days'  => $this->getTotalDays(true),
            'price' => $this->getPrice()->jsonSerialize()
        ];
    }
}
