<?php

namespace Boodmo\SalesTest\Entity;

use Boodmo\Sales\Entity\OrderBid;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderRma;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Entity\CancelReason;
use Boodmo\Sales\Model\Workflow\Status\Status;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Model\Workflow\Status\StatusListInterface;
use Boodmo\User\Entity\UserProfile\Supplier;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Doctrine\Common\Collections\ArrayCollection;

class OrderItemTest extends TestCase
{
    /**
     * @var OrderItem
     */
    protected $orderItem;

    /**
     * @covers \Boodmo\Sales\Entity\OrderItem::__construct
     */
    public function setUp()
    {
        $this->orderItem = new OrderItem();
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderItem::setId
     * @covers \Boodmo\Sales\Entity\OrderItem::getId
     */
    public function testSetGetId()
    {
        $id = Uuid::uuid4();
        $this->assertEquals($this->orderItem, $this->orderItem->setId($id));
        $this->assertEquals($id, $this->orderItem->getId());

        $this->expectExceptionMessage('ID must have uuid4 format.');
        $this->orderItem->setId('123456');
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderItem::setPackage
     * @covers \Boodmo\Sales\Entity\OrderItem::getPackage
     */
    public function testSetGetPackage()
    {
        $package = new OrderPackage();
        $this->assertEquals($this->orderItem, $this->orderItem->setPackage($package));
        $this->assertEquals($package, $this->orderItem->getPackage());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderItem::getParent
     */
    public function testGetParent()
    {
        $this->assertNull($this->orderItem->getParent());

        $package = new OrderPackage();
        $this->assertEquals($this->orderItem, $this->orderItem->setPackage($package));
        $this->assertEquals($package, $this->orderItem->getParent());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderItem::setProductId
     * @covers \Boodmo\Sales\Entity\OrderItem::getProductId
     */
    public function testSetGetProductId()
    {
        $this->assertEquals($this->orderItem, $this->orderItem->setProductId(111));
        $this->assertEquals(111, $this->orderItem->getProductId());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderItem::setPartId
     * @covers \Boodmo\Sales\Entity\OrderItem::getPartId
     */
    public function testSetGetPartId()
    {
        $this->assertEquals($this->orderItem, $this->orderItem->setPartId(111));
        $this->assertEquals(111, $this->orderItem->getPartId());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderItem::setName
     * @covers \Boodmo\Sales\Entity\OrderItem::getName
     */
    public function testSetGetName()
    {
        $this->assertEquals($this->orderItem, $this->orderItem->setName("Test item"));
        $this->assertEquals("Test item", $this->orderItem->getName());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderItem::setQty
     * @covers \Boodmo\Sales\Entity\OrderItem::getQty
     */
    public function testSetGetQty()
    {
        $this->assertEquals($this->orderItem, $this->orderItem->setQty(1));
        $this->assertEquals(1, $this->orderItem->getQty());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderItem::setPrice
     * @covers \Boodmo\Sales\Entity\OrderItem::getPrice
     */
    public function testSetGetPrice()
    {
        $this->assertEquals($this->orderItem, $this->orderItem->setPrice(100));
        $this->assertEquals($this->orderItem, $this->orderItem->setBasePrice(100));
        $this->assertEquals(100, $this->orderItem->getPrice());
        $this->assertEquals(100, $this->orderItem->getBasePrice());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderItem::setDeliveryPrice
     * @covers \Boodmo\Sales\Entity\OrderItem::getDeliveryPrice
     */
    public function testSetGetDeliveryPrice()
    {
        $this->assertEquals($this->orderItem, $this->orderItem->setDeliveryPrice(100));
        $this->assertEquals($this->orderItem, $this->orderItem->setBaseDeliveryPrice(100));
        $this->assertEquals(100, $this->orderItem->getDeliveryPrice());
        $this->assertEquals(100, $this->orderItem->getBaseDeliveryPrice());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderItem::getSubTotal
     * @covers \Boodmo\Sales\Entity\OrderItem::getBaseSubtotal
     */
    public function testGetSubTotal()
    {
        $this->assertEquals(0, $this->orderItem->getSubTotal());
        $this->orderItem->setPrice(100);
        $this->orderItem->setBasePrice(1000);
        $this->orderItem->setQty(2);
        $this->assertEquals(200, $this->orderItem->getSubTotal());
        $this->assertEquals(2000, $this->orderItem->getBaseSubtotal());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderItem::getCostTotal
     */
    public function testGetCostTotal()
    {
        $this->assertEquals(0, $this->orderItem->getCostTotal());
        $this->orderItem->setCost(100);
        $this->orderItem->setQty(2);
        $this->assertEquals(200, $this->orderItem->getCostTotal());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderItem::getGrandTotal
     */
    public function testGetGrandTotal()
    {
        $this->assertEquals(0, $this->orderItem->getGrandTotal());
        $this->orderItem->setPrice(100);
        $this->orderItem->setDeliveryPrice(50);
        $this->orderItem->setDiscount(10);
        $this->orderItem->setQty(2);
        $this->assertEquals(290, $this->orderItem->getGrandTotal());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderItem::setBrand
     * @covers \Boodmo\Sales\Entity\OrderItem::getBrand
     */
    public function testSetGetBrand()
    {
        $this->assertEquals($this->orderItem, $this->orderItem->setBrand("BOSCH"));
        $this->assertEquals("BOSCH", $this->orderItem->getBrand());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderItem::setFamily
     * @covers \Boodmo\Sales\Entity\OrderItem::getFamily
     */
    public function testSetGetFamily()
    {
        $this->assertNull($this->orderItem->getFamily());
        $this->assertEquals($this->orderItem, $this->orderItem->setFamily('Bolt'));
        $this->assertEquals('Bolt', $this->orderItem->getFamily());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderItem::setNumber
     * @covers \Boodmo\Sales\Entity\OrderItem::getNumber
     */
    public function testSetGetNumber()
    {
        $this->assertEquals($this->orderItem, $this->orderItem->setNumber("70 0011 12321"));
        $this->assertEquals("70 0011 12321", $this->orderItem->getNumber());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderItem::setOriginPrice
     * @covers \Boodmo\Sales\Entity\OrderItem::getOriginPrice
     */
    public function testSetGetOriginPrice()
    {
        $this->assertEquals($this->orderItem, $this->orderItem->setOriginPrice(100));
        $this->assertEquals(100, $this->orderItem->getOriginPrice());
    }

    public function testSetGetDiscount()
    {
        $this->assertEquals($this->orderItem, $this->orderItem->setDiscount(100));
        $this->assertEquals($this->orderItem, $this->orderItem->setBaseDiscount(100));
        $this->assertEquals(100, $this->orderItem->getDiscount());
        $this->assertEquals(100, $this->orderItem->getBaseDiscount());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderItem::setCost
     * @covers \Boodmo\Sales\Entity\OrderItem::getCost
     */
    public function testSetGetCost()
    {
        $this->assertEquals($this->orderItem, $this->orderItem->setCost(100));
        $this->assertEquals($this->orderItem, $this->orderItem->setBaseCost(100));
        $this->assertEquals(100, $this->orderItem->getCost());
        $this->assertEquals(100, $this->orderItem->getBaseCost());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderItem::setCancelReason
     * @covers \Boodmo\Sales\Entity\OrderItem::getCancelReason
     */
    public function testSetGetCancelReason()
    {
        $cancelReason = new CancelReason();
        $this->assertEquals($this->orderItem, $this->orderItem->setCancelReason($cancelReason));
        $this->assertEquals($cancelReason, $this->orderItem->getCancelReason());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderItem::isCancelled()
     */
    public function testIsCancelled()
    {
        $this->assertFalse($this->orderItem->isCancelled());
        $this->orderItem->setStatus([Status::TYPE_GENERAL => StatusEnum::CANCELLED]);
        $this->assertTrue($this->orderItem->isCancelled());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderItem::setDispatchDate
     * @covers \Boodmo\Sales\Entity\OrderItem::getDispatchDate
     */
    public function testSetGetDispatchDate()
    {
        $date = new \DateTime("23-04-1990");
        $this->assertEquals($this->orderItem, $this->orderItem->setDispatchDate($date));
        $this->assertEquals($date, $this->orderItem->getDispatchDate());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderItem::setLocked
     * @covers \Boodmo\Sales\Entity\OrderItem::getLocked
     */
    public function testSetGetLocked()
    {
        $this->assertEquals($this->orderItem, $this->orderItem->setLocked(true));
        $this->assertEquals(true, $this->orderItem->getLocked());
    }

    public function testClone()
    {
        $this->orderItem->setPrice(100);
        $this->orderItem->setCancelReason(new CancelReason());
        $this->orderItem->setPackage(new OrderPackage());
        $this->orderItem->addBid(new OrderBid());
        $newItem = clone $this->orderItem;
        $this->assertNotTrue($newItem->getId() === $this->orderItem->getId());
        $this->assertNull($newItem->getPackage());
        $this->assertNull($newItem->getCancelReason());
        $this->assertEquals(new ArrayCollection(), $newItem->getBids());
        $this->assertEquals(100, $newItem->getPrice());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderItem::setBids
     * @covers \Boodmo\Sales\Entity\OrderItem::getBids
     * @covers \Boodmo\Sales\Entity\OrderItem::addBid
     */
    public function testSetGetAddBid()
    {
        $this->assertEquals(new ArrayCollection(), $this->orderItem->getBids());

        $bids = new ArrayCollection();
        $bid1 = new OrderBid();
        $bid2 = new OrderBid();
        $bid3 = new OrderBid();
        $bids->add($bid1);
        $bids->add($bid2);
        $this->orderItem->setBids($bids);
        $this->assertEquals($bids, $this->orderItem->getBids());

        $bids->add($bid3);
        $this->orderItem->addBid($bid3);
        $this->assertEquals($bids, $this->orderItem->getBids());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderItem::createAcceptedBid
     */
    public function testCreateAcceptedBid()
    {
        $date = new \DateTime();
        $bids = new ArrayCollection();
        $bid1 = (new OrderBid())->setStatus(OrderBid::STATUS_OPEN);
        $bid2 = (new OrderBid())->setStatus(OrderBid::STATUS_ACCEPTED);
        $bid3 = (new OrderBid())->setStatus(OrderBid::STATUS_REJECTED);
        $bid4 = (new OrderBid())->setStatus(OrderBid::STATUS_MISSED);
        $bid5 = (new OrderBid())->setStatus(OrderBid::STATUS_CANCELLED);
        $bids->add($bid1);
        $bids->add($bid2);
        $bids->add($bid3);
        $bids->add($bid4);
        $bids->add($bid5);
        $this->orderItem->setBids($bids)
            ->setPrice(10025)
            ->setCost(10026)
            ->setDispatchDate($date)
            ->setPackage((new OrderPackage())->setSupplierProfile((new Supplier())->setId(1)));

        $newBid = $this->orderItem->createAcceptedBid();
        $this->assertEquals(OrderBid::STATUS_REJECTED, $bid1->getStatus(), 'bid1: STATUS_OPEN->STATUS_REJECTED');
        $this->assertEquals(OrderBid::STATUS_REJECTED, $bid2->getStatus(), 'bid2: STATUS_ACCEPTED->STATUS_REJECTED');
        $this->assertEquals(OrderBid::STATUS_REJECTED, $bid3->getStatus(), 'bid3: STATUS_REJECTED->STATUS_REJECTED');
        $this->assertEquals(OrderBid::STATUS_MISSED, $bid4->getStatus(), 'bid4: STATUS_MISSED->STATUS_MISSED');
        $this->assertEquals(OrderBid::STATUS_CANCELLED, $bid5->getStatus(), 'bid5: STATUS_CANCELLED->STATUS_CANCELLED');
        $this->assertEquals(10025, $newBid->getPrice());
        $this->assertEquals(10026, $newBid->getCost());
        $this->assertEquals($date, $newBid->getDispatchDate());
        $this->assertEquals(1, $newBid->getSupplierProfile()->getId());
        $this->assertEquals(OrderBid::STATUS_ACCEPTED, $newBid->getStatus());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderItem::getConfirmationDate
     * @covers \Boodmo\Sales\Entity\OrderItem::setConfirmationDate
     */
    public function testSetGetConfirmationDate()
    {
        $this->assertNull($this->orderItem->getConfirmationDate());

        $date = new \DateTimeImmutable('2017-10-10 10:11:12');
        $this->orderItem->setConfirmationDate($date);
        $this->assertEquals($date, $this->orderItem->getConfirmationDate());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderItem::getRmaList
     * @covers \Boodmo\Sales\Entity\OrderItem::setRmaList
     * @covers \Boodmo\Sales\Entity\OrderItem::addRma
     */
    public function testSetGetRmaList()
    {
        $packages = new ArrayCollection();
        $packages->add(new OrderPackage());
        $this->orderItem->setPackage(
            $this->createConfiguredMock(
                OrderPackage::class,
                [
                    'getBundle' => $this->createConfiguredMock(
                        OrderBundle::class,
                        ['getPackages' => $packages]
                    )
                ]
            )
        );
        $rma1 = new OrderRma();
        $rma2 = new OrderRma();
        $rma3 = new OrderRma();
        $collection = new ArrayCollection();
        $collection->add($rma1);
        $collection->add($rma2);
        $this->assertEquals($this->orderItem, $this->orderItem->setRmaList($collection));
        $this->assertEquals($collection, $this->orderItem->getRmaList());
        $collection->add($rma3);
        $this->assertEquals($this->orderItem, $this->orderItem->addRma($rma3));
        $this->assertEquals($collection, $this->orderItem->getRmaList());
    }

    public function testIsReplaced()
    {
        $this->assertFalse($this->orderItem->isReplaced());

        $this->orderItem->setCancelReason((new CancelReason())->setId(CancelReason::ITEM_WAS_REPLACED));
        $this->assertTrue($this->orderItem->isReplaced());

        $this->orderItem->setCancelReason(null)
            ->setStatus([Status::TYPE_GENERAL => StatusEnum::CANCELLED]);
        $this->assertFalse($this->orderItem->isReplaced());

        $this->orderItem->setCancelReason(null)
            ->setStatus([])
            ->triggerStatusHistory(
                $this->createMock(StatusListInterface::class),
                $this->createMock(StatusListInterface::class),
                ['child' => '123']
            );
        $this->assertFalse($this->orderItem->isReplaced());

        $this->orderItem->setCancelReason(null)
            ->setStatus([Status::TYPE_GENERAL => StatusEnum::CANCELLED])
            ->triggerStatusHistory(
                $this->createMock(StatusListInterface::class),
                $this->createMock(StatusListInterface::class),
                ['child' => '123']
            );
        $this->assertTrue($this->orderItem->isReplaced());

        $this->orderItem->setCancelReason((new CancelReason())->setId(CancelReason::ITEM_WAS_REPLACED))
            ->setStatus([Status::TYPE_GENERAL => StatusEnum::CANCELLED])
            ->triggerStatusHistory(
                $this->createMock(StatusListInterface::class),
                $this->createMock(StatusListInterface::class),
                ['child' => '123']
            );
        $this->assertTrue($this->orderItem->isReplaced());
    }
}
