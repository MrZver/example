<?php
/**
 * Created by PhpStorm.
 * User: Shandy
 * Date: 30.07.2017
 * Time: 13:56
 */

namespace Boodmo\SalesTest\Model;

use Boodmo\Sales\Model\Seller;
use Boodmo\Shipping\Model\Location;
use PHPUnit\Framework\TestCase;

class SellerTest extends TestCase
{
    /**
     * @var Seller
     */
    private $seller;

    protected function setUp()
    {
        $this->seller = new Seller(1, 'test', 2, 'India', 'HA', 'Deli', true);
    }

    public function testName()
    {
        $this->assertEquals('test', $this->seller->getName());
    }

    public function testSupplierId()
    {
        $this->assertEquals(1, $this->seller->getSupplierId());
    }

    public function testDispatchDays()
    {
        $this->assertEquals(2, $this->seller->getDispatchDays());
    }

    public function testIsCod()
    {
        $this->assertEquals(true, $this->seller->isCod());
    }

    public function testGetLocation()
    {
        $location = $this->seller->getLocation();
        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals('India', $location->getCountry());
        $this->assertEquals('HA', $location->getState());
        $this->assertEquals('Deli', $location->getCity());
        $this->assertFalse($location->isUnknown());
    }

    public function testToArray()
    {
        $this->assertEquals(['id' => 1, 'name' => 'test', 'dispatch_days' => 2], $this->seller->toArray());

        $seller = new Seller(2, 'test2', 4, 'India', 'HA', 'Deli', true);
        $this->assertEquals(['id' => 2, 'name' => 'test2', 'dispatch_days' => 4], $seller->toArray());
    }

    public function testToString()
    {
        $this->assertEquals('1 test', (string)$this->seller);

        $seller = new Seller(2, 'test2', 4, 'India', 'HA', 'Deli', true);
        $this->assertEquals('2 test2', (string)$seller);
    }
}
