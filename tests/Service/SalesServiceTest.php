<?php

namespace Boodmo\SalesTest\Service;

use Boodmo\Catalog\Service\SupplierPartService;
use Boodmo\Currency\Service\MoneyService;
use Boodmo\Media\Service\MediaService;
use Boodmo\Sales\Service\SalesService;
use Boodmo\Shipping\Entity\Country;
use Boodmo\Shipping\Repository\CountryRepository;
use Boodmo\Shipping\Service\DeliveryFinderService;
use Boodmo\User\Entity\Address;
use Boodmo\User\Entity\UserProfile\Supplier;
use Boodmo\User\Service\SupplierService;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class SalesServiceTest extends TestCase
{
    /**
     * @var SalesService
     */
    protected $service;

    /**
     * @var SupplierPartService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $supplierPartService;

    /**
     * @var DeliveryFinderService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $deliveryFinderService;

    /**
     * @var MoneyService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $moneyService;

    /**
     * @var SupplierService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $supplierService;

    /**
     * @var CountryRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $countryRepository;

    /**
     * @var MediaService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mediaService;

    /**
     * @var \ReflectionMethod
     */
    protected $getSellerByCurrencyMethod;

    public function setUp()
    {
        $this->supplierPartService = $this->createMock(SupplierPartService::class);
        $this->deliveryFinderService = $this->createMock(DeliveryFinderService::class);
        $this->moneyService = $this->createMock(MoneyService::class);
        $this->supplierService = $this->createPartialMock(SupplierService::class, ['loadSupplierProfileByCurrency']);
        $this->countryRepository = $this->createMock(CountryRepository::class);
        $this->mediaService = $this->createMock(MediaService::class);

        $this->service = new SalesService(
            $this->supplierPartService,
            $this->deliveryFinderService,
            $this->moneyService,
            $this->supplierService,
            $this->countryRepository,
            $this->mediaService
        );

        $reflector = new \ReflectionObject($this->service);
        $this->getSellerByCurrencyMethod = $reflector->getMethod('getSellerByCurrency');
        $this->getSellerByCurrencyMethod->setAccessible(true);
    }

    public function testGetSellerByCurrency()
    {
        $id = 1;
        $currency = 'INR';
        $addresses = new ArrayCollection();
        $address1 = (new Address())->setCountry((new Country())->setId(1)->setName('India'))
            ->setState('State1')
            ->setCity('City1')
            ->setType(Address::TYPE_BILLING);
        $address2 = (new Address())->setCountry((new Country())->setId(2)->setName('India2'))
            ->setState('State2')
            ->setCity('City2')
            ->setType(Address::TYPE_SHIPPING);
        $addresses->add($address1);
        $addresses->add($address2);

        $supplier = (new Supplier())
            ->setId(1)
            ->setName('Test Seller')
            ->setDefaultDispatchDays(10)
            ->setCashDelivery(false)
            ->setAddresses($addresses);
        $this->supplierService->method('loadSupplierProfileByCurrency')->willReturn($supplier);
        $this->assertEquals(
            [
                'supplier_id'           => 1,
                'supplier_name'         => 'Test Seller',
                'defaultDispatchDays'   => 10,
                'cod'                   => false,
                'supplier_country'      => 2,
                'supplier_state'        => 'State2',
                'supplier_city'         => 'City2',
            ],
            $this->getSellerByCurrencyMethod->invoke($this->service, $id, $currency)
        );
    }
}
