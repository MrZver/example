<?php
/**
 * Created by PhpStorm.
 * User: Shandy
 * Date: 30.07.2017
 * Time: 15:51
 */

namespace Boodmo\SalesTest\Model;

use Boodmo\Currency\Service\CurrencyService;
use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Model\Delivery;
use Boodmo\Sales\Model\DeliveryBuilder;
use Boodmo\Sales\Model\Product;
use Boodmo\Sales\Model\ProductBuilder;
use Boodmo\Sales\Model\Seller;
use Boodmo\Shipping\Entity\Logistics;
use Boodmo\Shipping\Entity\ShippingCalculation;
use Boodmo\Shipping\Model\DeliveryFinderInterface;
use Boodmo\Shipping\Model\Location;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class DeliveryTest extends TestCase
{
    /**
     * @var Delivery
     */
    private $delivery;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DeliveryBuilder
     */
    private $builder;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Product
     */
    private $product;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Seller
     */
    private $seller;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Logistics
     */
    private $logistic;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DeliveryFinderInterface
     */
    private $finder;

    public function setUp()
    {
        $this->builder = $this->createMock(DeliveryBuilder::class);
        $this->product = $this->createMock(Product::class);
        $this->product->builder = $this->createConfiguredMock(
            ProductBuilder::class,
            [
                'getConverter' =>  $this->getMockBuilder(MoneyService::class)
                    ->setConstructorArgs(
                        [$this->createConfiguredMock(CurrencyService::class, ['getCurrencyRate' => 65.00])]
                    )
                    ->setMethods(['getMoney'])
                    ->getMock()
            ]
        );
        $this->seller = $this->createMock(Seller::class);
        $this->logistic = $this->createMock(Logistics::class);
        $this->delivery = new Delivery($this->builder, $this->product);
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(Delivery::class, $this->delivery);
    }

    public function testFromLocation()
    {
        $this->product->expects($this->once())
            ->method('getSeller')
            ->willReturn($this->seller);
        $this->seller->expects($this->once())
            ->method('getLocation')
            ->willReturn($this->stubLocation('India', 'HA', 'Deli'));

        $location = $this->delivery->fromLocation();
        $this->assertEquals('India', $location->getCountry());
        $this->assertEquals('HA', $location->getState());
        $this->assertEquals('Deli', $location->getCity());
        $this->assertFalse($location->isUnknown());
    }

    public function testToLocation()
    {
        $this->builder->expects($this->any())
            ->method('getLocation')
            ->willReturn($this->stubLocation('India', 'HA', 'Deli'));
        $location = $this->delivery->toLocation();
        $this->assertEquals('India', $location->getCountry());
        $this->assertEquals('HA', $location->getState());
        $this->assertEquals('Deli', $location->getCity());
        $this->assertFalse($location->isUnknown());
    }

    public function testToLocationWhenUnknown()
    {
        $this->builder->expects($this->once())
            ->method('getLocation')
            ->willReturn($this->stubLocation());
        $this->builder->expects($this->once())
            ->method('getDefaultLocation');

        $location = $this->delivery->toLocation();
        $this->assertEquals('', $location->getCountry());
        $this->assertEquals('', $location->getState());
        $this->assertEquals('', $location->getCity());
        $this->assertFalse($location->isUnknown());
    }

    public function testGetLogistic()
    {
        $this->prepareGetLogistic();
        $logistic = $this->delivery->getLogistic();
        $this->assertInstanceOf(Logistics::class, $logistic);
    }

    public function testGetDays()
    {
        $this->prepareGetLogistic();
        $this->logistic->expects($this->once())
            ->method('getDays')
            ->willReturn(1);
        $days = $this->delivery->getDays();
        $this->assertEquals(1, $days);
    }

    public function testGetShippingETA()
    {
        $this->prepareGetLogistic();
        $this->logistic->expects($this->once())
            ->method('getDays')
            ->willReturn(1);
        $this->seller->expects($this->once())
            ->method('getDispatchDays')
            ->willReturn(2);
        $now = new \DateTime();
        $date = $this->delivery->getShippingETA();
        $this->assertInstanceOf(\DateTime::class, $date);
        $this->assertEquals(3, $date->diff($now)->days);
    }

    public function testGetPrices()
    {
        $this->prepareGetLogistic();
        $this->logistic->expects($this->once())
                    ->method('getPrice')
                    ->willReturn(100);
        $this->product->expects($this->once())
            ->method('getFamilyId')
            ->willReturn(5);
        $shipping = $this->createMock(ShippingCalculation::class);
        $this->finder->expects($this->once())
            ->method('findShippingCalculation')
            ->with(5)
            ->willReturn($shipping);
        $this->product->expects($this->once())
            ->method('getRequestedQty')
            ->willReturn(3);
        $shipping->expects($this->once())
            ->method('getQuantityMultiplicator')
            ->willReturn([]);
        $shipping->expects($this->once())
            ->method('getTaxModifier')
            ->willReturn(1);
        $shipping->expects($this->once())
            ->method('getSizeModifier')
            ->willReturn(100);
        $this->product->expects($this->once())
            ->method('getPrice')
            ->willReturn(new Money(100, new Currency('INR')));

        $price = $this->delivery->getPrice();
        $basePrice = $this->delivery->getBasePrice();
        $this->assertEquals('20000', $price->getAmount());
        $this->assertEquals('20000', $basePrice->getAmount());
    }

    public function testGetPricesWithMultiplicators()
    {
        $this->markTestIncomplete();
    }

    public function testGetPricesWithUsdProduct()
    {
        $this->markTestIncomplete();
    }

    private function prepareGetLogistic(): void
    {
        $fromLocation = $this->stubLocation('India', 'HA', 'Deli');
        $toLocation = $this->stubLocation('India', 'HA2', 'Deli2');
        //Mocking for fromLocation
        $this->product->expects($this->any())
            ->method('getSeller')
            ->willReturn($this->seller);
        $this->seller->expects($this->once())
            ->method('getLocation')
            ->willReturn($fromLocation);
        //Mocking for toLocation
        $this->builder->expects($this->any())
            ->method('getLocation')
            ->willReturn($toLocation);
        //Mocking for DeliveryFinder
        $this->finder = $this->createMock(DeliveryFinderInterface::class);
        $this->builder->expects($this->any())
            ->method('getDeliveryFinder')
            ->willReturn($this->finder);
        $this->finder->expects($this->once())
            ->method('findLogistic')
            ->with($fromLocation, $toLocation)
            ->willReturn($this->logistic);
    }

    private function stubLocation(string $country = '', string $state = '', string $city = '')
    {
        $location = $this->createMock(Location::class);
        $location->expects($this->any())
            ->method('getCountry')
            ->willReturn($country);
        $location->expects($this->any())
            ->method('getState')
            ->willReturn($state);
        $location->expects($this->any())
            ->method('getCity')
            ->willReturn($city);
         $location->expects($this->any())
            ->method('isUnknown')
            ->willReturn($country === '' || $state === '' || $city === '');
        return $location;
    }
}
