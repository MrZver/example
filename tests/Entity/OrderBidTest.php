<?php

namespace Boodmo\SalesTest\Entity;

use Boodmo\User\Entity\UserProfile\Supplier;
use Boodmo\Sales\Entity\OrderBid;
use Boodmo\Sales\Entity\OrderItem;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class OrderBidTest extends TestCase
{
    /**
     * @var OrderBid
     */
    private $entity;

    public function setUp()
    {
        $this->entity = new OrderBid();
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderBid::setId
     * @covers \Boodmo\Sales\Entity\OrderBid::getId
     * @covers \Boodmo\Sales\Entity\OrderBid::__construct
     */
    public function testSetGetId()
    {
        $id = Uuid::uuid4();
        $this->assertEquals($this->entity, $this->entity->setId($id));
        $this->assertEquals($id, $this->entity->getId());

        $this->expectExceptionMessage('ID must have uuid4 format.');
        $this->entity->setId('123456');
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderBid::getStatus
     * @covers \Boodmo\Sales\Entity\OrderBid::setStatus
     */
    public function testSetGetStatus()
    {
        $this->assertEquals(OrderBid::STATUS_OPEN, $this->entity->getStatus());
        $this->assertEquals($this->entity, $this->entity->setStatus('test_status'));
        $this->assertEquals('test_status', $this->entity->getStatus());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderBid::setPrice
     * @covers \Boodmo\Sales\Entity\OrderBid::getPrice
     */
    public function testSetGetPrice()
    {
        $this->assertEquals($this->entity, $this->entity->setPrice(100));
        $this->assertEquals(100, $this->entity->getPrice());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderBid::setCost
     * @covers \Boodmo\Sales\Entity\OrderBid::getCost
     */
    public function testSetGetCost()
    {
        $this->assertEquals($this->entity, $this->entity->setCost(100));
        $this->assertEquals(100, $this->entity->getCost());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderBid::getOrderItem
     * @covers \Boodmo\Sales\Entity\OrderBid::setOrderItem
     */
    public function testSetGetOrderItem()
    {
        $orderItem = new OrderItem();
        $this->assertEquals($this->entity, $this->entity->setOrderItem($orderItem));
        $this->assertEquals($orderItem, $this->entity->getOrderItem());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderBid::setSupplierProfile
     * @covers \Boodmo\Sales\Entity\OrderBid::getSupplierProfile
     */
    public function testSetGetSupplierProfile()
    {
        $supplier = new Supplier();
        $this->entity->setSupplierProfile($supplier);
        $this->assertSame($supplier, $this->entity->getSupplierProfile());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderBid::setDispatchDate
     * @covers \Boodmo\Sales\Entity\OrderBid::getDispatchDate
     */
    public function testSetGetDispatchDate()
    {
        $date = new \DateTime();
        $this->assertEquals($this->entity, $this->entity->setDispatchDate($date));
        $this->assertEquals($date, $this->entity->getDispatchDate());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderBid::setDeliveryDays
     * @covers \Boodmo\Sales\Entity\OrderBid::getDeliveryDays
     */
    public function testSetGetDeliveryDays()
    {
        $this->assertEquals($this->entity, $this->entity->setDeliveryDays(5));
        $this->assertEquals(5, $this->entity->getDeliveryDays());
    }
}
