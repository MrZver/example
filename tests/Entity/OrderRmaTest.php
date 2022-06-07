<?php

namespace Boodmo\SalesTest\Entity;

use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Entity\OrderRma;
use Boodmo\Sales\Entity\OrderItem;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class OrderRmaTest extends TestCase
{
    /**
     * @var OrderRma
     */
    private $entity;

    public function setUp()
    {
        $this->entity = new OrderRma();
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderRma::setId
     * @covers \Boodmo\Sales\Entity\OrderRma::getId
     * @covers \Boodmo\Sales\Entity\OrderRma::__construct
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
     * @covers \Boodmo\Sales\Entity\OrderRma::getNumber
     * @covers \Boodmo\Sales\Entity\OrderRma::setNumber
     */
    public function testSetGetNumber()
    {
        $this->assertEquals('', $this->entity->getNumber());
        $this->assertEquals($this->entity, $this->entity->setNumber('test_number'));
        $this->assertEquals('test_number', $this->entity->getNumber());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderRma::getQty
     * @covers \Boodmo\Sales\Entity\OrderRma::setQty
     */
    public function testSetGetQty()
    {
        $this->assertEquals(0, $this->entity->getQty());
        $this->assertEquals($this->entity, $this->entity->setQty(15));
        $this->assertEquals(15, $this->entity->getQty());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderRma::getIntent
     * @covers \Boodmo\Sales\Entity\OrderRma::setIntent
     */
    public function testSetGetIntent()
    {
        $this->assertEquals('', $this->entity->getIntent());
        $this->assertEquals($this->entity, $this->entity->setIntent('test_intent'));
        $this->assertEquals('test_intent', $this->entity->getIntent());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderRma::getReason
     * @covers \Boodmo\Sales\Entity\OrderRma::setReason
     */
    public function testSetGetReason()
    {
        $this->assertEquals('', $this->entity->getReason());
        $this->assertEquals($this->entity, $this->entity->setReason('test_reason'));
        $this->assertEquals('test_reason', $this->entity->getReason());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderRma::getStatus
     * @covers \Boodmo\Sales\Entity\OrderRma::setStatus
     */
    public function testSetGetStatus()
    {
        $this->assertEquals(OrderRma::STATUS_REQUESTED, $this->entity->getStatus());
        $this->assertEquals($this->entity, $this->entity->setStatus('test_status'));
        $this->assertEquals('test_status', $this->entity->getStatus());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderRma::getOrderItem
     * @covers \Boodmo\Sales\Entity\OrderRma::setOrderItem
     */
    public function testSetGetOrderItem()
    {
        $orderItem = new OrderItem();
        $this->assertEquals($this->entity, $this->entity->setOrderItem($orderItem));
        $this->assertEquals($orderItem, $this->entity->getOrderItem());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderRma::generateNumber
     */
    public function testGenerateNumber()
    {
        $bundleId = '1234';
        $bundle = new OrderBundle();
        $bundle->setId($bundleId);
        $package = new OrderPackage();
        $item = new OrderItem();

        $bundle->addPackage($package);
        $package->setBundle($bundle);

        $package->addItem($item);
        $item->setPackage($package);

        $this->entity->setOrderItem($item);
        $item->addRma($this->entity);

        $this->assertEquals("$bundleId/2", $this->entity->generateNumber());
    }
}
