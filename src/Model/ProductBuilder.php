<?php

namespace Boodmo\Sales\Model;

use Boodmo\Currency\Service\MoneyService;
use Boodmo\Media\Service\MediaService;
use Money\Converter;
use Money\Currency;
use Money\Money;

class ProductBuilder
{
    public const ID_KEY = 'id';
    public const PART_KEY = 'part_id';
    public const PART = 'part';
    public const FAMILY_KEY = 'family_id';
    public const SELLER_KEY = 'seller';
    public const PRICE_KEY = 'price';
    public const BASE_PRICE_KEY = 'base_price';
    public const MRP_KEY = 'mrp';
    public const COST_KEY = 'cost';
    public const BASE_COST_KEY = 'base_cost';
    public const QTY_KEY = 'requested_qty';
    private $inquiryQty = 1;
    private $currency;
    private $rawData = [];
    /**
     * @var MoneyService
     */
    private $moneyService;

    /**
     * ProductBuilder constructor.
     *
     * @param MoneyService $moneyService
     * @param MediaService $mediaService
     */
    public function __construct(MoneyService $moneyService, MediaService $mediaService)
    {
        $this->moneyService = $moneyService;
        $this->mediaService = $mediaService;
    }

    public function build(int $inquiryQty = 1, string $currency = null): Product
    {
        $this->inquiryQty($inquiryQty)->toCurrency($currency);
        return new Product($this);
    }

    public function inquiryQty(int $inquiryQty): self
    {
        $this->inquiryQty = $inquiryQty;
        return $this;
    }

    public function toCurrency(?string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    private function toInt(string $key): int
    {
        return (int)($this->rawData[$key] ?? 0);
    }

    public function isDirty(): bool
    {
        return $this->toInt(self::ID_KEY) < 1
            && $this->toInt(self::PART_KEY) < 1
            && $this->toInt(self::FAMILY_KEY) < 1
            && $this->toInt('supplier_id') < 1
            && ($this->rawData['supplier_name'] ?? '') === ''
            && ($this->rawData['supplier_country'] ?? '') === ''
            && ($this->rawData['supplier_state'] ?? '') === ''
            && ($this->rawData['supplier_city'] ?? '') === ''
            && ($this->rawData['currency'] ?? '') === ''
            && ($this->rawData['cod'] ?? '') === ''
            && $this->toInt('defaultDispatchDays') < 1
            && $this->toInt(self::PRICE_KEY) < 1
            && $this->toInt(self::COST_KEY) < 1
            && $this->toInt(self::MRP_KEY) < 1;
    }

    /**
     * @return array
     * @throws \LogicException
     */
    public function getData(): array
    {
        if ($this->isDirty()) {
            throw new \LogicException('Builder should be fully configured.');
        }
        $seller = new Seller(
            $this->toInt('supplier_id'),
            $this->rawData['supplier_name'],
            $this->toInt('defaultDispatchDays'),
            $this->rawData['supplier_country'],
            $this->rawData['supplier_state'],
            $this->rawData['supplier_city'],
            $this->rawData['cod'] ?? false
        );

        $part = [
            'id' => $this->rawData[self::PART_KEY] ?? '',
            'name' => $this->rawData['name'] ?? '',
            'slug' => $this->rawData['slug'] ?? '',
            'sku' => $this->rawData['sku'] ?? '',
            'number' => $this->rawData['number'] ?? '',
            'image' => $this->rawData['attributes']['main_image'] ?? '',
            'family_id' => $this->rawData[self::FAMILY_KEY] ?? '',
            'family_name' => $this->rawData['family_name'] ?? '',
            'brand_name' => $this->rawData['brand_name'] ?? '',
            'brand_is_oem' => $this->rawData['brand_is_oem'] ?? '',
            'brand_code' => $this->rawData['brand_code'] ?? '',
            'attributes' => $this->rawData['attributes'] ?? []
        ];
        if (empty($part['image']) and !empty($this->rawData['family_image'])) {
            $part['image'] = $this->rawData['family_image'];
        }
        if (empty($part['image'])) {
            $part['image'] = $this->mediaService->getPlaceholder('catalog_list');
        }
        $part['attributes']['is_best_offer'] = !empty($part['attributes']['is_best_offer']);

        //Prepare finance data
        $cost  = new Money($this->toInt(self::COST_KEY), new Currency($this->rawData['currency']));
        $price = new Money($this->toInt(self::PRICE_KEY), new Currency($this->rawData['currency']));
        $mrp   = new Money($this->toInt(self::MRP_KEY), new Currency($this->rawData['currency']));

        $basePrice = $price;
        $baseCost = $cost;
        $round = $this->rawData['currency'] !== MoneyService::BASE_CURRENCY ? Money::ROUND_UP : null;

        if ($this->rawData['currency'] !== MoneyService::BASE_CURRENCY) {
            $basePrice = $this->moneyService->convert($price, new Currency(MoneyService::BASE_CURRENCY), $round);
            $baseCost = $this->moneyService->convert($cost, new Currency(MoneyService::BASE_CURRENCY), $round);
        }

        //Check need to build product in same currency
        if ($this->currency !== null && $this->rawData['currency'] !== $this->currency) {
            $cost  = $this->moneyService->convert($cost, new Currency($this->currency), $round);
            $price = $this->moneyService->convert($price, new Currency($this->currency), $round);
            $mrp   = $this->moneyService->convert($mrp, new Currency($this->currency), $round);
        }

        return [
            self::ID_KEY => $this->toInt(self::ID_KEY),
            self::PART_KEY => $this->toInt(self::PART_KEY),
            self::FAMILY_KEY => $this->toInt(self::FAMILY_KEY),
            self::SELLER_KEY => $seller,
            self::PRICE_KEY => $price,
            self::COST_KEY => $cost,
            self::BASE_COST_KEY => $baseCost,
            self::MRP_KEY => $mrp,
            self::BASE_PRICE_KEY => $basePrice,
            self::QTY_KEY => $this->inquiryQty,
            self::PART => $part
        ];
    }

    /**
     * @param array $rawData
     *
     * @return ProductBuilder
     */
    public function setRawData(array $rawData): self
    {
        $this->rawData = $rawData;
        return $this;
    }

    public function setId(int $id)
    {
        $this->rawData[self::ID_KEY] = $id;
        return $this;
    }

    public function setPartId(int $id)
    {
        $this->rawData[self::PART_KEY] = $id;
        return $this;
    }

    public function setFamilyId(int $id)
    {
        $this->rawData[self::FAMILY_KEY] = $id;
        return $this;
    }

    public function setFamilyName(string $name)
    {
        $this->rawData['family_name'] = $name;
        return $this;
    }

    public function setFamilyImage(string $image)
    {
        $this->rawData['family_image'] = $image;
        return $this;
    }

    public function setSupplierId(int $id)
    {
        $this->rawData['supplier_id'] = $id;
        return $this;
    }

    public function setPrice(int $price)
    {
        $this->rawData[self::PRICE_KEY] = $price;
        return $this;
    }

    public function setCost(int $cost)
    {
        $this->rawData[self::COST_KEY] = $cost;
        return $this;
    }

    public function setMrp(int $mrp)
    {
        $this->rawData[self::MRP_KEY] = $mrp;
        return $this;
    }

    public function setSupplierName(string $name)
    {
        $this->rawData['supplier_name'] = $name;
        return $this;
    }

    public function setSupplierCountry(string $country)
    {
        $this->rawData['supplier_country'] = $country;
        return $this;
    }

    public function setSupplierState(string $state)
    {
        $this->rawData['supplier_state'] = $state;
        return $this;
    }

    public function setSupplierCity(string $city)
    {
        $this->rawData['supplier_city'] = $city;
        return $this;
    }

    public function setSupplierDays(int $days)
    {
        $this->rawData['defaultDispatchDays'] = $days;
        return $this;
    }

    public function setCurrency(string $currency)
    {
        $this->rawData['currency'] = $currency;
        return $this;
    }

    public function setName(string $name)
    {
        $this->rawData['name'] = $name;
        return $this;
    }

    public function setNumber(string $number)
    {
        $this->rawData['number'] = $number;
        return $this;
    }

    public function setSlug(string $slug)
    {
        $this->rawData['slug'] = $slug;
        return $this;
    }

    public function setSku(string $sku)
    {
        $this->rawData['sku'] = $sku;
        return $this;
    }

    public function setBradName(string $brand)
    {
        $this->rawData['brand_name'] = $brand;
        return $this;
    }

    public function setBradCode(string $code)
    {
        $this->rawData['brand_code'] = $code;
        return $this;
    }

    public function setIsOemBrad(bool $is_oem)
    {
        $this->rawData['brand_is_oem'] = $is_oem;
        return $this;
    }

    public function setAttributes(array $attributes)
    {
        $this->rawData['attributes'] = $attributes;
        return $this;
    }

    /**
     * @return MoneyService
     */
    public function getConverter(): MoneyService
    {
        return $this->moneyService;
    }

    public function setSeller(array $sellerData)
    {
        $keys = [
            'supplier_id',
            'supplier_name',
            'defaultDispatchDays',
            'supplier_country',
            'supplier_state',
            'supplier_city',
            'cod'
        ];
        foreach ($keys as $key_name) {
            if (isset($sellerData[$key_name])) {
                $this->rawData[$key_name] = $sellerData[$key_name];
            }
        }
        return $this;
    }
}
