<?php

namespace Boodmo\SalesTest\Entity;

use Boodmo\Sales\Entity\CancelReason;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Entity\Payment;
use Boodmo\Sales\Model\Workflow\Status\Status;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Shipping\Entity\ShippingBox;
use Boodmo\User\Entity\Address;
use Boodmo\User\Entity\UserProfile\Supplier;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class OrderPackageTest extends TestCase
{
    /**
     * @var OrderPackage
     */
    protected $orderPackage;
    /**
     * @var OrderItem
     */
    private $item1;
    /**
     * @var OrderItem
     */
    private $item2;
    /**
     * @var OrderItem
     */
    private $item3;
    /**
     * @var OrderItem
     */
    private $item4;

    /**
     * @covers \Boodmo\Sales\Entity\OrderPackage::__construct
     */
    public function setUp()
    {
        $this->orderPackage = new OrderPackage();
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderPackage::setId
     * @covers \Boodmo\Sales\Entity\OrderPackage::getId
     */
    public function testSetGetId()
    {
        $this->assertEquals($this->orderPackage, $this->orderPackage->setId(1));
        $this->assertEquals(1, $this->orderPackage->getId());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderPackage::setBundle
     * @covers \Boodmo\Sales\Entity\OrderPackage::getBundle
     */
    public function testSetGetBundle()
    {
        $bundle = new OrderBundle();
        $this->assertEquals($this->orderPackage, $this->orderPackage->setBundle($bundle));
        $this->assertEquals($bundle, $this->orderPackage->getBundle());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderPackage::setSupplierProfile
     * @covers \Boodmo\Sales\Entity\OrderPackage::getSupplierProfile
     */
    public function testSetGetSupplierProfile()
    {
        $this->assertNull($this->orderPackage->getSupplierProfile());

        //the same currency
        $supplier = (new Supplier())->setBaseCurrency('INR');
        $this->orderPackage->setCurrency('INR')->setSupplierProfile($supplier);
        $this->assertSame($supplier, $this->orderPackage->getSupplierProfile());

        //when different currency
        $this->orderPackage->setCurrency('INR')->setId(2);
        $this->expectExceptionMessage('Package (id: 2) & Supplier (id: 1) have different currency (INR != USD).');
        $this->orderPackage->setSupplierProfile((new Supplier())->setBaseCurrency('USD')->setId(1));
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderPackage::setDeliveryDays
     * @covers \Boodmo\Sales\Entity\OrderPackage::getDeliveryDays
     */
    public function testSetGetDeliveryDays()
    {
        $this->assertEquals($this->orderPackage, $this->orderPackage->setDeliveryDays(1));
        $this->assertEquals(1, $this->orderPackage->getDeliveryDays());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderPackage::getDeliveryTotal
     */
    public function testGetDeliveryTotal()
    {
        $this->assertEquals(0, $this->orderPackage->getDeliveryTotal());
        $this->addStubItems($this->orderPackage);
        $this->assertEquals(1500, $this->orderPackage->getDeliveryTotal());
        $this->assertEquals(15000, $this->orderPackage->getBaseDeliveryTotal());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderPackage::getSubTotal
     */
    public function testGetSubTotal()
    {
        $this->assertEquals(0, $this->orderPackage->getSubTotal());
        $this->addStubItems($this->orderPackage);
        $this->assertEquals(5000, $this->orderPackage->getSubTotal());
        $this->assertEquals(50000, $this->orderPackage->getBaseSubTotal());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderPackage::getGrandTotal
     */
    public function testGetGrandTotal()
    {
        $this->assertEquals(0, $this->orderPackage->getGrandTotal());
        $this->addStubItems($this->orderPackage);
        $this->assertEquals(6400, $this->orderPackage->getGrandTotal());
        $this->assertEquals(64000, $this->orderPackage->getBaseGrandTotal());

        $this->orderPackage->getBundle()->setSumCanceledItemsFlag(true);
        $this->assertEquals(16800, $this->orderPackage->getGrandTotal());
        $this->assertEquals(168000, $this->orderPackage->getBaseGrandTotal());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderPackage::getOriginalTotal
     * @covers \Boodmo\Sales\Entity\OrderPackage::getBaseOriginalPriceTotal
     */
    public function testGetOriginalTotal()
    {
        $this->assertEquals(0, $this->orderPackage->getOriginalTotal());
        $this->assertEquals(0, $this->orderPackage->getBaseOriginalPriceTotal());
        $this->addStubItems($this->orderPackage);
        $this->assertEquals(2206, $this->orderPackage->getOriginalTotal());
        $this->assertEquals(22060, $this->orderPackage->getBaseOriginalPriceTotal());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderPackage::getCostTotal
     * @covers \Boodmo\Sales\Entity\OrderPackage::getBaseCostTotal
     */
    public function testGetCostTotal()
    {
        $this->assertEquals(0, $this->orderPackage->getCostTotal());
        $this->assertEquals(0, $this->orderPackage->getBaseCostTotal());
        $this->addStubItems($this->orderPackage);
        $this->assertEquals(4900, $this->orderPackage->getCostTotal());
        $this->assertEquals(49000, $this->orderPackage->getBaseCostTotal());
    }

    public function testGetDiscountTotal()
    {
        $this->assertEquals(0, $this->orderPackage->getDiscountTotal());
        $this->addStubItems($this->orderPackage);
        $this->assertEquals(100, $this->orderPackage->getDiscountTotal());
        $this->assertEquals(1000, $this->orderPackage->getBaseDiscountTotal());
    }

    public function testGetFacilitationFee()
    {
        $this->assertEquals(0, $this->orderPackage->getFacilitationFee());
        $this->addStubItems($this->orderPackage);
        $this->assertEquals(100, $this->orderPackage->getFacilitationFee());

        $this->orderPackage->setSupplierProfile(
            (new Supplier())->setAccountingAgent(
                (new Supplier())->setAccounting(['self' => ['commission' => 11.15]])
            )
        );
        $this->assertEquals(558, $this->orderPackage->getFacilitationFee(true), '5000 * 0.1115');
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderPackage::setNumber
     * @covers \Boodmo\Sales\Entity\OrderPackage::getNumber
     */
    public function testSetGetNumber()
    {
        $this->assertEquals($this->orderPackage, $this->orderPackage->setNumber(1));
        $this->assertEquals(1, $this->orderPackage->getNumber());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderPackage::getFullNumber
     */
    public function testGetFullNumber()
    {
        $this->orderPackage->setNumber(1);
        $this->orderPackage->setBundle(
            (new OrderBundle())->setCreatedAt(new \DateTime('2017-04-20'))->setId(1234)
        );
        $this->assertEquals('2004/801234-1', $this->orderPackage->getFullNumber());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderPackage::setInvoiceNumber
     * @covers \Boodmo\Sales\Entity\OrderPackage::getInvoiceNumber
     */
    public function testSetGetInvoiceNumber()
    {
        $this->assertEquals($this->orderPackage, $this->orderPackage->setInvoiceNumber("test"));
        $this->assertEquals("test", $this->orderPackage->getInvoiceNumber());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderPackage::setItems
     * @covers \Boodmo\Sales\Entity\OrderPackage::getItems
     * @covers \Boodmo\Sales\Entity\OrderPackage::addItem
     */
    public function testSetGetItems()
    {
        $bundle = new OrderBundle();
        $bundle->addPackage($this->orderPackage);
        $item1 = new OrderItem();
        $item2 = new OrderItem();
        $item3 = new OrderItem();
        $collection = new ArrayCollection();
        $collection->add($item1);
        $collection->add($item2);
        $this->assertEquals($this->orderPackage, $this->orderPackage->setItems($collection));
        $this->assertEquals($collection, $this->orderPackage->getItems());
        $collection->add($item3);
        $this->assertEquals($this->orderPackage, $this->orderPackage->addItem($item3));
        $this->assertEquals($collection, $this->orderPackage->getItems());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderPackage::setShippingETA
     * @covers \Boodmo\Sales\Entity\OrderPackage::getShippingETA
     */
    public function testSetGetShippingETA()
    {
        $now = new \DateTime();
        $this->assertEquals($this->orderPackage, $this->orderPackage->setShippingETA($now));
        $this->assertEquals($now, $this->orderPackage->getShippingETA());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderPackage::setDeliveredAt
     * @covers \Boodmo\Sales\Entity\OrderPackage::getDeliveredAt
     */
    public function testSetGetDeliveredAt()
    {
        $now = new \DateTime();
        $this->assertEquals($this->orderPackage, $this->orderPackage->setDeliveredAt($now));
        $this->assertEquals($now, $this->orderPackage->getDeliveredAt());
    }


    /**
     * @covers \Boodmo\Sales\Entity\OrderPackage::setInvoiceSnapshot
     * @covers \Boodmo\Sales\Entity\OrderPackage::getInvoiceSnapshot
     */
    public function testSetGetInvoiceSnapshot()
    {
        $snapshot = [
            "test" => [
                "snapshot" => "test data"
            ]
        ];
        $this->assertEquals($this->orderPackage, $this->orderPackage->setInvoiceSnapshot($snapshot));
        $this->assertEquals($snapshot, $this->orderPackage->getInvoiceSnapshot());
    }

    public function testGetChildren()
    {
        $this->addStubItems($this->orderPackage);
        $this->assertEquals(3, $this->orderPackage->getChildren()->count());
    }

    public function testSetGetCurrency()
    {
        $this->assertEquals($this->orderPackage, $this->orderPackage->setCurrency("USD"));
        $this->assertEquals("USD", $this->orderPackage->getCurrency());
    }

    public function testGetItemFilter()
    {
        $this->addStubItems($this->orderPackage);
        $func = $this->orderPackage->getItemFilter();
        $this->assertFalse($func($this->item3));
    }

    public function testGetSourceState()
    {
        $this->orderPackage->setSupplierProfile(new Supplier());
        $this->assertEquals('HARYANA', $this->orderPackage->getSourceState());
        $this->orderPackage->getSupplierProfile()->getAddresses()->add(
            (new Address())->setState('test')->setType('billing')
        );
        $this->assertEquals('test', $this->orderPackage->getSourceState());
    }

    public function testGetDestinationState()
    {
        $this->addStubItems($this->orderPackage);
        $this->assertEquals('HARYANA', $this->orderPackage->getDestinationState());
        $this->orderPackage->getBundle()->setCustomerAddress(['state' => 'test']);
        $this->assertEquals('test', $this->orderPackage->getDestinationState());
    }

    public function testGetActiveItems()
    {
        $this->addStubItems($this->orderPackage);
        $this->assertEquals(2, $this->orderPackage->getActiveItems()->count());
        $this->item2->setStatus([Status::TYPE_GENERAL => StatusEnum::CANCEL_REQUESTED_USER]);
        $this->item1->setStatus([Status::TYPE_GENERAL => StatusEnum::CANCEL_REQUESTED_SUPPLIER]);
        $this->assertEquals(0, $this->orderPackage->getActiveItems()->count());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderPackage::setExternalInvoice
     * @covers \Boodmo\Sales\Entity\OrderPackage::getExternalInvoice
     */
    public function testSetGetExternalInvoice()
    {
        $this->assertNull($this->orderPackage->getExternalInvoice());

        $this->assertEquals($this->orderPackage, $this->orderPackage->setExternalInvoice('12345'));
        $this->assertEquals('12345', $this->orderPackage->getExternalInvoice());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderPackage::setFacilitationInvoiceNumber
     * @covers \Boodmo\Sales\Entity\OrderPackage::getFacilitationInvoiceNumber
     */
    public function testSetGetFacilitationInvoiceNumber()
    {
        $this->assertNull($this->orderPackage->getFacilitationInvoiceNumber());

        $this->assertEquals($this->orderPackage, $this->orderPackage->setFacilitationInvoiceNumber('123456'));
        $this->assertEquals('123456', $this->orderPackage->getFacilitationInvoiceNumber());
    }

    /**
     * Result in package:
     * SubTotal = (1000*1) + (2000*2) = 5000
     * DeliveryTotal = (500*1) + (500*2) = 1500
     * CostTotal = (900*1) + (2000*2) = 4900
     * DiscountTotal = 100 + 0 = 100
     * GrandTotal = 5000 + 1500 - 100 = 6400
     *
     * For *Base* totals all in results can * 10
     *
     * @param OrderPackage $package
     */
    protected function addStubItems(OrderPackage $package): void
    {
        $this->item1 = new OrderItem();
        $this->item2 = new OrderItem();
        $this->item3 = new OrderItem();
        $this->item4 = new OrderItem();

        $this->item1->setPrice(1000);
        $this->item1->setBasePrice(10000);
        $this->item1->setDeliveryPrice(500);
        $this->item1->setBaseDeliveryPrice(5000);
        $this->item1->setCost(900);
        $this->item1->setBaseCost(9000);
        $this->item1->setDiscount(100);
        $this->item1->setBaseDiscount(1000);
        $this->item1->setQty(1);
        $this->item1->setOriginPrice(901);
        $this->item1->setBaseOriginPrice(9010);

        $this->item2->setPrice(2000);
        $this->item2->setBasePrice(20000);
        $this->item2->setDeliveryPrice(500);
        $this->item2->setBaseDeliveryPrice(5000);
        $this->item2->setCost(2000);
        $this->item2->setBaseCost(20000);
        $this->item2->setDiscount(0);
        $this->item2->setBaseDiscount(0);
        $this->item2->setQty(2);
        $this->item2->setOriginPrice(201);
        $this->item2->setBaseOriginPrice(2010);

        $this->item3->setPrice(3000);
        $this->item3->setBasePrice(30000);
        $this->item3->setDeliveryPrice(500);
        $this->item3->setBaseDeliveryPrice(5000);
        $this->item3->setCost(2000);
        $this->item3->setBaseCost(20000);
        $this->item3->setDiscount(100);
        $this->item3->setBaseDiscount(1000);
        $this->item3->setQty(3);
        $this->item3->setOriginPrice(301);
        $this->item3->setBaseOriginPrice(3010);
        $this->item3->setStatus([Status::TYPE_GENERAL => StatusEnum::CANCELLED]);

        $this->item4->setPrice(4000);
        $this->item4->setBasePrice(40000);
        $this->item4->setDeliveryPrice(600);
        $this->item4->setBaseDeliveryPrice(6000);
        $this->item4->setCost(7000);
        $this->item4->setBaseCost(70000);
        $this->item4->setDiscount(800);
        $this->item4->setBaseDiscount(9000);
        $this->item4->setQty(4);
        $this->item4->setOriginPrice(801);
        $this->item4->setBaseOriginPrice(8010);
        $this->item4->setStatus([Status::TYPE_GENERAL => StatusEnum::CANCELLED]);
        $this->item4->setCancelReason((new CancelReason())->setId(CancelReason::ITEM_WAS_REPLACED));


        $bundle = new OrderBundle();
        $bundle->addPackage($package);
        $package->addItem($this->item1);
        $package->addItem($this->item2);
        $package->addItem($this->item3);
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderPackage::setShippingBox
     * @covers \Boodmo\Sales\Entity\OrderPackage::getShippingBox
     */
    public function testSetGetShippingBox()
    {
        $this->assertEquals(null, $this->orderPackage->getShippingBox());

        $shippingBox = new ShippingBox();
        $this->orderPackage->setShippingBox($shippingBox);
        $this->assertEquals($shippingBox, $this->orderPackage->getShippingBox());
    }
}
