<?php

namespace Boodmo\Sales\Service;

use Boodmo\Backend\ImportV2\Service\EmexService;
use Boodmo\Catalog\Entity\Part;
use Boodmo\Catalog\Service\SupplierPartService;
use Boodmo\Currency\Service\MoneyService;
use Boodmo\Media\Service\MediaService;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Model\DeliveryBuilder;
use Boodmo\Sales\Model\Offer;
use Boodmo\Sales\Model\PriceList;
use Boodmo\Sales\Model\ProductBuilder;
use Boodmo\Shipping\Model\DeliveryFinderInterface;
use Boodmo\Shipping\Model\Location;
use Boodmo\Shipping\Repository\CountryRepository;
use Boodmo\Shipping\Service\DeliveryFinderService;
use Boodmo\User\Entity\Address;
use Boodmo\User\Entity\UserProfile\Supplier;
use Boodmo\User\Service\SupplierService;

class SalesService
{
    /** @var SupplierPartService */
    private $supplierPartService;

    /** @var  DeliveryFinderInterface */
    private $deliveryFinderService;

    /**
     * @var MoneyService
     */
    private $moneyService;

    /**
     * @var SupplierService
     */
    private $supplierService;

    /**
     * @var MediaService
     */
    private $mediaService;

    /**
     * @var CountryRepository
     */
    private $countryRepository;

    public function __construct(
        SupplierPartService $supplierPartService,
        DeliveryFinderService $deliveryFinderService,
        MoneyService $moneyService,
        SupplierService $supplierService,
        CountryRepository $countryRepository,
        MediaService $mediaService
    ) {
        $this->supplierPartService      = $supplierPartService;
        $this->deliveryFinderService    = $deliveryFinderService;
        $this->moneyService             = $moneyService;
        $this->supplierService          = $supplierService;
        $this->countryRepository        = $countryRepository;
        $this->mediaService             = $mediaService;
    }

    /**
     * @param Part $part
     * @param array $location
     * @param string $currency
     *
     * @return PriceList
     */
    public function getActualPriceList(
        Part $part,
        array $location = [],
        string $currency = MoneyService::BASE_CURRENCY
    ): PriceList {
        $prices = $this->supplierPartService->loadArraySuppliersPricesByPart($part);
        $offers = [];
        $deliveryLocation = empty($location)
            ? null
            : new Location($location['country_id'], $location['state'], $location['city']);

        foreach ($prices as $price) {
            $productBuilder  = new ProductBuilder($this->moneyService, $this->mediaService);
            $deliveryBuilder = new DeliveryBuilder($this->deliveryFinderService, $deliveryLocation);
            $deliveryBuilder->setDefaultCountry($this->countryRepository->getDefaultCountry());

            $productBuilder = $productBuilder->setRawData($price);
            $productBuilder->setName($part->getName())
                ->setNumber($part->getNumber())
                ->setSlug($part->getSlug())
                ->setSku($part->getSku())
                ->setBradName($part->getBrand()->getName())
                ->setBradCode($part->getBrand()->getCode())
                ->setIsOemBrad($part->getBrand()->isOem())
                ->setFamilyId($part->getFamily()->getId())
                ->setFamilyName($part->getFamily()->getName())
                ->setAttributes($part->getAttributes())
                ->setMrp(
                    $this->getMrp(
                        $price['supplier_id'],
                        $price[ProductBuilder::PRICE_KEY],
                        $price[ProductBuilder::MRP_KEY],
                        $price['locality']
                    )
                );
            $productBuilder->setSeller($this->getSellerByCurrency($price['supplier_id'], $currency));
            if ($familyImage = $part->getFamily()->getImage()) {
                $productBuilder->setFamilyImage($familyImage);
            }

            $offers[] = new Offer($productBuilder, $deliveryBuilder, 1, 0, $currency);
        }

        return new PriceList($part->getId(), $offers);
    }

    public function getOfferByProductId(
        int $productId,
        Location $location,
        ?string $currency = MoneyService::BASE_CURRENCY
    ): ?Offer {
        $result = null;
        if ($product = $this->supplierPartService->loadArraySupplierPriceById($productId)) {
            $productBuilder = new ProductBuilder($this->moneyService, $this->mediaService);
            $deliveryBuilder = new DeliveryBuilder($this->deliveryFinderService, $location);
            $deliveryBuilder->setDefaultCountry($this->countryRepository->getDefaultCountry());
            $productBuilder->setRawData($product)
                ->setMrp(
                    $this->getMrp(
                        $product['supplier_id'],
                        $product[ProductBuilder::PRICE_KEY],
                        $product[ProductBuilder::MRP_KEY],
                        $product['locality']
                    )
                );
            $productBuilder->setSeller($this->getSellerByCurrency($product['supplier_id'], $currency));
            $result = new Offer($productBuilder, $deliveryBuilder, 1, 0, $currency);
        }
        return $result;
    }

    private function getSellerByCurrency($id, $currency): array
    {
        /* @var Address $sellerAddress */
        $supplier = $this->supplierService->loadSupplierProfileByCurrency($id, $currency);
        $sellerData = [
            'supplier_id'           => $supplier->getId(),
            'supplier_name'         => $supplier->getOnlineName(),
            'defaultDispatchDays'   => $supplier->getDefaultDispatchDays(),
            'cod'                   => $supplier->isCashDelivery(),
        ];
        if ($sellerAddress = $supplier->getAddresses(Address::TYPE_SHIPPING)) {
            $country = $sellerAddress->getCountry();
            $sellerData['supplier_country'] = $country ? $country->getId() : 0;
            $sellerData['supplier_state']   = $sellerAddress->getState();
            $sellerData['supplier_city']    = $sellerAddress->getCity();
        }
        return $sellerData;
    }

    public function getMrp(int $supplierId, $price, $mrp, string $locality): int
    {
        $result = $mrp;
        if (!empty($price) && \in_array($supplierId, Supplier::EMEX_SUPPLIERS_IDS, true)) {
            $result = $price + $price * EmexService::PRICE_TO_MRP_PERCENT_INCREASE / 100;
        } elseif (empty($mrp) && $locality === Supplier::LOCALITY_LOCAL) {
            $result = $price;
        }
        return $result;
    }

    public function getOfferByItemAndSupplier(OrderItem $orderItem, Supplier $supplier)
    {
        $result = null;
        $customerAddress = $orderItem->getPackage()->getBundle()->getCustomerAddress();

        $mainSupplierProfile = $supplier->getParent() ?? $supplier;
        $supplierUserId      = $mainSupplierProfile->getUserInfo()->getId();
        $supplierPart        = $this->supplierPartService->loadSupplierPartBySupplierAndPart(
            $supplierUserId,
            $orderItem->getPartId()
        );
        $supplierPart = $supplierPart ?? $this->supplierPartService->loadSupplierPart($orderItem->getProductId());
        if ($supplierPart) {
            $productBuilder = new ProductBuilder($this->moneyService, $this->mediaService);
            $part = $supplierPart->getPart();
            $supplierShippingAddresses = $supplier->getAddresses(Address::TYPE_SHIPPING);

            $productBuilder->setRawData(
                [
                    ProductBuilder::ID_KEY => $supplierPart->getId(),
                    ProductBuilder::PART_KEY => $part->getId(),
                    ProductBuilder::FAMILY_KEY => $part->getFamily()->getId(),
                    ProductBuilder::PRICE_KEY => $supplierPart->getPrice(),
                    ProductBuilder::COST_KEY => $supplierPart->getCost(),
                    ProductBuilder::MRP_KEY => $supplierPart->getMrp() ?? 0,
                ]
            )
                ->setCurrency($orderItem->getPackage()->getCurrency())
                ->setName($part->getName())
                ->setNumber($part->getNumber())
                ->setSlug($part->getSlug())
                ->setSku($part->getSku())
                ->setBradName($part->getBrand()->getName())
                ->setBradCode($part->getBrand()->getCode())
                ->setIsOemBrad($part->getBrand()->isOem())
                ->setFamilyName($part->getFamily()->getName())
                ->setAttributes($part->getAttributes())
                ->setSeller([
                    'supplier_id' => $supplier->getId(),
                    'supplier_name' => $supplier->getName(),
                    'defaultDispatchDays' => $supplier->getDefaultDispatchDays(),
                    'supplier_country' => $supplierShippingAddresses->getCountry()->getId() ?? '',
                    'supplier_state' => $supplierShippingAddresses->getState() ?? '',
                    'supplier_city' => $supplierShippingAddresses->getCity() ?? '',
                    'cod' => $supplier->isCashDelivery()
                ]);
            $deliveryBuilder = new DeliveryBuilder(
                $this->deliveryFinderService,
                new Location($customerAddress['country'], $customerAddress['state'], $customerAddress['city'])
            );
            $result = new Offer(
                $productBuilder,
                $deliveryBuilder,
                $orderItem->getQty(),
                0,
                $supplier->getBaseCurrency()
            );
        }

        return $result;
    }
}
