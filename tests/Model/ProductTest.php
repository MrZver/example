<?php
/**
 * Created by PhpStorm.
 * User: Shandy
 * Date: 30.07.2017
 * Time: 14:36
 */

namespace Boodmo\SalesTest\Model;

use Boodmo\Sales\Model\Product;
use Boodmo\Sales\Model\ProductBuilder;
use Boodmo\Sales\Model\Seller;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    /**
     * @var Product
     */
    private $product;
    private $builder;

    protected function setUp()
    {
        $seller = $this->prophesize(Seller::class);
        $seller->toArray()->willReturn([
            'name' => 'test',
        ]);
        $usd = new Currency('USD');
        $inr = new Currency('INR');
        $this->builder = $this->prophesize(ProductBuilder::class);
        $this->builder->getData()->willReturn([
            ProductBuilder::ID_KEY         => 1,
            ProductBuilder::PART_KEY       => 2,
            ProductBuilder::FAMILY_KEY     => 3,
            ProductBuilder::SELLER_KEY     => $seller->reveal(),
            ProductBuilder::PRICE_KEY      => new Money(100, $usd),
            ProductBuilder::BASE_PRICE_KEY => new Money(1000, $inr),
            ProductBuilder::MRP_KEY        => new Money(150, $usd),
            ProductBuilder::COST_KEY       => new Money(90, $usd),
            ProductBuilder::BASE_COST_KEY  => new Money(900, $inr),
            ProductBuilder::QTY_KEY        => 1,
            ProductBuilder::PART           => []
        ]);
        $this->product = new Product($this->builder->reveal());
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(Product::class, $this->product);
    }

    public function testId()
    {
        $this->assertEquals(1, $this->product->getId());
    }

    public function testPartId()
    {
        $this->assertEquals(2, $this->product->getPartId());
    }

    public function testFamilyId()
    {
        $this->assertEquals(3, $this->product->getFamilyId());
    }

    public function testRequestedQty()
    {
        $this->assertEquals(1, $this->product->getRequestedQty());
    }

    public function testSeller()
    {
        $this->assertInstanceOf(Seller::class, $this->product->getSeller());
    }

    public function testPrice()
    {
        $this->assertEquals('100', $this->product->getPrice()->getAmount());
        $this->assertEquals('USD', $this->product->getPrice()->getCurrency()->getCode());
    }

    public function testBasePrice()
    {
        $this->assertEquals('1000', $this->product->getBasePrice()->getAmount());
        $this->assertEquals('INR', $this->product->getBasePrice()->getCurrency()->getCode());
    }

    public function testCost()
    {
        $this->assertEquals('90', $this->product->getCost()->getAmount());
        $this->assertEquals('USD', $this->product->getCost()->getCurrency()->getCode());
    }

    public function testBaseCost()
    {
        $this->assertEquals('900', $this->product->getBaseCost()->getAmount());
        $this->assertEquals('INR', $this->product->getBaseCost()->getCurrency()->getCode());
    }

    public function testMrp()
    {
        $this->assertEquals('150', $this->product->getMrp()->getAmount());
    }

    public function testSafePercent()
    {
        $this->assertEquals(33, $this->product->getSafePercent());
    }

    public function testSafeTotal()
    {
        $this->assertEquals('50', $this->product->getSafeTotal()->getAmount());
    }

    public function testToArray()
    {
        $result = $this->product->toArray();
        $expected = [
            ProductBuilder::ID_KEY         => 1,
            ProductBuilder::PART_KEY       => 2,
            ProductBuilder::FAMILY_KEY     => 3,
            ProductBuilder::PART           => [],
            ProductBuilder::SELLER_KEY     => ['name' => 'test'],
            ProductBuilder::PRICE_KEY      => [
                'amount'   => '100',
                'currency' => 'USD',
            ],
            ProductBuilder::BASE_PRICE_KEY => [
                'amount'   => '1000',
                'currency' => 'INR',
            ],
            ProductBuilder::MRP_KEY        => [
                'amount'   => '150',
                'currency' => 'USD',
            ],
            ProductBuilder::COST_KEY       => [
                'amount'   => '90',
                'currency' => 'USD',
            ],
            ProductBuilder::BASE_COST_KEY  => [
                'amount'   => '900',
                'currency' => 'INR',
            ],
            ProductBuilder::QTY_KEY        => 1,
            'safe_total' => [
                'amount'   => '50',
                'currency' => 'USD',
            ],
            'safe_percent' => 33,
            'money'        => new Money(100, new Currency('USD')),
        ];
        $this->assertEquals($expected, $result);
    }
}
