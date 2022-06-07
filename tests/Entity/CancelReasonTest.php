<?php

namespace Boodmo\SalesTest\Entity;

use Boodmo\Sales\Entity\CancelReason;
use PHPUnit\Framework\TestCase;

class CancelReasonTest extends TestCase
{
    /**
     * @var Entity
     */
    protected $cancelReason;

    /**
     * @covers \Boodmo\Sales\Entity\CancelReason::__construct
     */
    public function setUp()
    {
        $cancelReason = new CancelReason();
        $this->cancelReason = $cancelReason;
    }

    /**
     * @covers \Boodmo\Sales\Entity\CancelReason::setId
     * @covers \Boodmo\Sales\Entity\CancelReason::getId
     */
    public function testSetGetId()
    {
        $this->assertEquals($this->cancelReason, $this->cancelReason->setId(1));
        $this->assertEquals(1, $this->cancelReason->getId());
    }

    /**
     * @covers \Boodmo\Sales\Entity\CancelReason::setName
     * @covers \Boodmo\Sales\Entity\CancelReason::getName
     */
    public function testSetGetName()
    {
        $this->assertEquals($this->cancelReason, $this->cancelReason->setName('test'));
        $this->assertEquals('test', $this->cancelReason->getName());
    }

    /**
     * @covers \Boodmo\Sales\Entity\CancelReason::setSort
     * @covers \Boodmo\Sales\Entity\CancelReason::getSort
     */
    public function testSetGetSort()
    {
        $this->assertEquals($this->cancelReason, $this->cancelReason->setSort(1));
        $this->assertEquals(1, $this->cancelReason->getSort());
    }

    /**
     * @covers \Boodmo\Sales\Entity\CancelReason::setCustom
     * @covers \Boodmo\Sales\Entity\CancelReason::getCustom
     */
    public function testSetGetCustom()
    {
        $this->assertEquals($this->cancelReason, $this->cancelReason->setCustom(true));
        $this->assertEquals(1, $this->cancelReason->getCustom());
    }

    public function testConstants()
    {
        $this->assertEquals(1, CancelReason::CUSTOMER_PRICE_CHANGED);
        $this->assertEquals(2, CancelReason::CUSTOMER_DELIVERY_CHANGED);
        $this->assertEquals(3, CancelReason::CUSTOMER_CHANGED_MIND);
        $this->assertEquals(4, CancelReason::CUSTOMER_NOT_REACHABLE);
        $this->assertEquals(5, CancelReason::CANT_DELIVER);
        $this->assertEquals(6, CancelReason::SUPPLIER_NO_STOCK);
        $this->assertEquals(7, CancelReason::OTHER);
        $this->assertEquals(8, CancelReason::CUSTOMER_NO_VENDORS);
        $this->assertEquals(9, CancelReason::DUPLICATE);
        $this->assertEquals(10, CancelReason::TEST);
        $this->assertEquals(11, CancelReason::ITEM_WAS_REPLACED);
        $this->assertEquals(12, CancelReason::NO_COD_AVAILABLE);
        $this->assertEquals(13, CancelReason::CUSTOM_CANCELLED_HIMSELF);
    }
}
