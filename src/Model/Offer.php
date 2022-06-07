<?php

namespace Boodmo\Sales\Model;

use Boodmo\Shipping\Model\Location;
use Money\Money;

class Offer implements LocationProcessingInterface
{
    /** @var ProductBuilder */
    private $productBuilder;

    /** @var DeliveryBuilder */
    private $deliveryBuilder;

    /** @var  Product */
    private $product;

    /** @var  Delivery */
    private $delivery;

    /** @var int */
    private $inquiry;

    /** @var int */
    private $amountDiscount;

    /** @var string */
    private $currency;

    /**
     * Offer constructor.
     *
     * @param ProductBuilder $productBuilder
     * @param DeliveryBuilder $deliveryBuilder
     * @param int|null $inquiry
     * @param int $amountDiscount
     * @param string|null $currency
     */
    public function __construct(
        ProductBuilder $productBuilder,
        DeliveryBuilder $deliveryBuilder,
        int $inquiry = 1,
        int $amountDiscount = 0,
        string $currency = null
    ) {
        $this->productBuilder = $productBuilder;
        $this->deliveryBuilder = $deliveryBuilder;
        $this->inquiry = $inquiry;
        $this->amountDiscount = $amountDiscount;
        $this->currency = $currency;
    }

    public function getProduct(): Product
    {
        if ($this->product === null) {
            $this->product = $this->productBuilder->build($this->inquiry, $this->currency);
        }
        return $this->product;
    }

    public function getDelivery(): Delivery
    {
        if ($this->delivery === null) {
            $this->delivery = $this->deliveryBuilder->build($this->getProduct());
        }
        return $this->delivery;
    }

    public function getBaseTotalPrice(): Money
    {
        return $this->getProduct()->getBasePrice()->add($this->getDelivery()->getBasePrice());
    }

    public function applyLocation(Location $location): self
    {
        return new self(
            $this->productBuilder,
            $this->deliveryBuilder->applyLocation($location),
            $this->inquiry,
            $this->amountDiscount,
            $this->currency
        );
    }

    public function inquiryQty(int $qty): self
    {
        return new self(
            $this->productBuilder,
            $this->deliveryBuilder,
            $qty,
            $this->amountDiscount,
            $this->currency
        );
    }

    public function applyDiscount(int $amount): self
    {
        return new self(
            $this->productBuilder,
            $this->deliveryBuilder,
            $this->inquiry,
            $amount,
            $this->currency
        );
    }

    public function toCurrency(?string $currency): self
    {
        return new self(
            $this->productBuilder,
            $this->deliveryBuilder,
            $this->inquiry,
            $this->amountDiscount,
            $currency
        );
    }

    public function getDiscount(): Money
    {
        return new Money($this->amountDiscount, $this->getProduct()->getPrice()->getCurrency());
    }

    public function getBaseDiscount(): Money
    {
        $baseCurrency = $this->getProduct()->getBasePrice()->getCurrency();
        $amount       = $this->getDiscount()->getCurrency()->equals($baseCurrency) ?
            $this->getDiscount()->getAmount() :
            $this->productBuilder->getConverter()->convert($this->getDiscount(), $baseCurrency);

        return new Money($amount, $baseCurrency);
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        return [
            'product'  => $this->getProduct()->toArray(),
            'delivery' => $this->getDelivery()->toArray()
        ];
    }
}
