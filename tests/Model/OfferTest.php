<?php

namespace Boodmo\SalesTest\Model;

use Boodmo\Sales\Model\DeliveryBuilder;
use Boodmo\Sales\Model\Offer;
use Boodmo\Sales\Model\Product;
use Boodmo\Sales\Model\ProductBuilder;
use PHPUnit\Framework\TestCase;

class OfferTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductBuilder
     */
    private $productBuilder;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DeliveryBuilder
     */
    private $deliveryBuilder;

    public function setUp()
    {
        $this->productBuilder = $this->createMock(ProductBuilder::class);
        $this->deliveryBuilder = $this->createMock(DeliveryBuilder::class);
    }

    public function testConstructor()
    {
        $offer = new Offer($this->productBuilder, $this->deliveryBuilder);
        $this->assertInstanceOf(Offer::class, $offer);
        $offer = new Offer($this->productBuilder, $this->deliveryBuilder, 2, 30, 'INR');
        $this->assertInstanceOf(Offer::class, $offer);
    }

    public function testGetProduct()
    {
        $offer = new Offer($this->productBuilder, $this->deliveryBuilder);
        $mock = $this->createMock(Product::class);
        $this->productBuilder->expects($this->once())
            ->method('build')
            ->with(1, null)
            ->willReturn($mock);
        $product = $offer->getProduct();
        $this->assertSame($mock, $product);
    }

    public function testGetProductWithQtyAndCurrency()
    {
        $offer = new Offer($this->productBuilder, $this->deliveryBuilder, 2, 0, 'USD');
        $mock = $this->createMock(Product::class);
        $this->productBuilder->expects($this->once())
            ->method('build')
            ->with(2, 'USD')
            ->willReturn($mock);
        $product = $offer->getProduct();
        $this->assertSame($mock, $product);
    }
}
