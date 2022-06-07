<?php

namespace Boodmo\Sales\Model;

use Money\Money;

class Product
{
    private $basePrice;
    private $mrp;
    private $price;
    private $cost;
    /** @var Seller */
    private $seller;
    private $familyId;
    private $partId;
    private $attributes;
    private $id;
    private $qty = 1;
    private $baseCost;
    private $part = [];
    public $builder;

    /**
     * Product constructor.
     *
     * @param ProductBuilder $builder
     */
    public function __construct(ProductBuilder $builder)
    {
        [
            $builder::ID_KEY => $this->id,
            $builder::PART_KEY => $this->partId,
            $builder::FAMILY_KEY => $this->familyId,
            $builder::SELLER_KEY => $this->seller,
            $builder::PRICE_KEY => $this->price,
            $builder::BASE_PRICE_KEY => $this->basePrice,
            $builder::MRP_KEY => $this->mrp,
            $builder::COST_KEY => $this->cost,
            $builder::BASE_COST_KEY => $this->baseCost,
            $builder::QTY_KEY => $this->qty,
            $builder::PART => $this->part
        ] = $builder->getData();

        $this->builder = $builder;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPartId(): int
    {
        return $this->partId;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getPart(): array
    {
        return $this->part;
    }

    public function getFamilyId(): int
    {
        return $this->familyId;
    }

    public function getSeller(): Seller
    {
        return $this->seller;
    }

    public function getPrice(): Money
    {
        return $this->price;
    }

    public function getBasePrice(): Money
    {
        return $this->basePrice;
    }

    public function getMrp(): Money
    {
        return $this->mrp;
    }

    public function getCost(): Money
    {
        return $this->cost;
    }

    public function getBaseCost(): Money
    {
        return $this->baseCost;
    }

    public function getRequestedQty(): int
    {
        return $this->qty;
    }

    public function getSafePercent(): int
    {
        $mrp = (int) $this->getMrp()->getAmount();
        $price = (int) $this->getPrice()->getAmount();
        if ($mrp > $price) {
            return 100 - (round($price / $mrp * 100) ?? 100);
        }
        return 0;
    }

    public function getSafeTotal(): Money
    {
        $price = $this->getPrice();
        $mrp = $this->getMrp();
        if ($mrp->greaterThan($price)) {
            return $mrp->subtract($price);
        }
        return new Money(0, $price->getCurrency());
    }

    public function toArray(): array
    {
        return [
            'id'            => $this->getId(),
            'part_id'       => $this->getPartId(),
            ProductBuilder::FAMILY_KEY     => $this->getFamilyId(),
            'part'          => $this->getPart(),
            'base_price'    => $this->getBasePrice()->jsonSerialize(),
            'price'         => $this->getPrice()->jsonSerialize(),
            'money'         => $this->getPrice(),
            'mrp'           => $this->getMrp()->jsonSerialize(),
            'cost'          => $this->getCost()->jsonSerialize(),
            'base_cost'      => $this->getBaseCost()->jsonSerialize(),
            'safe_total'    => $this->getSafeTotal()->jsonSerialize(),
            'safe_percent'  => $this->getSafePercent(),
            'requested_qty' => $this->getRequestedQty(),
            'seller'        => $this->getSeller()->toArray()
        ];
    }
}
