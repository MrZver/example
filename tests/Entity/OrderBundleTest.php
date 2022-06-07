<?php

namespace Boodmo\SalesTest\Entity;

use Boodmo\Sales\Entity\CreditMemo;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Entity\Payment;
use Boodmo\Sales\Model\Workflow\Status\StatusProviderAggregateInterface;
use Boodmo\User\Entity\UserProfile\Customer;
use Doctrine\Common\Collections\ArrayCollection;
use Money\Money;
use Money\Currency;
use PHPUnit\Framework\TestCase;

class OrderBundleTest extends TestCase
{
    /**
     * @var OrderBundle
     */
    protected $orderBundle;
    /**
     * @var OrderPackage
     */
    private $package;
    /**
     * @var OrderItem
     */
    private $item;
    /**
     * @var OrderItem
     */
    private $item2;

    /**
     * @covers \Boodmo\Sales\Entity\OrderBundle::__construct
     */
    public function setUp()
    {
        $this->orderBundle = new OrderBundle();
    }

    public function testBaseClass()
    {
        $this->assertInstanceOf(StatusProviderAggregateInterface::class, $this->orderBundle);
        $this->assertNull($this->orderBundle->getCreatedAt());
        $this->assertNull($this->orderBundle->getUpdatedAt());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderBundle::setId
     * @covers \Boodmo\Sales\Entity\OrderBundle::getId
     */
    public function testSetGetId()
    {
        $this->assertNull($this->orderBundle->getId());
        $this->assertEquals($this->orderBundle, $this->orderBundle->setId(1));
        $this->assertEquals(1, $this->orderBundle->getId());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderBundle::setCustomerProfile
     * @covers \Boodmo\Sales\Entity\OrderBundle::getCustomerProfile
     */
    public function testSetGetCustomerProfile()
    {
        $customer = new Customer();
        $this->assertEquals($this->orderBundle, $this->orderBundle->setCustomerProfile($customer));
        $this->assertEquals($customer, $this->orderBundle->getCustomerProfile());
    }

    public function testSetGetCustomerAddress()
    {
        $this->assertEquals($this->orderBundle, $this->orderBundle->setCustomerAddress(['pin' => '123456']));
        $this->assertEquals(['pin' => '123456'], $this->orderBundle->getCustomerAddress());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderBundle::setCustomerEmail
     * @covers \Boodmo\Sales\Entity\OrderBundle::getCustomerEmail
     */
    public function testSetGetCustomerEmail()
    {
        $this->assertEquals($this->orderBundle, $this->orderBundle->setCustomerEmail('test@test.com'));
        $this->assertEquals('test@test.com', $this->orderBundle->getCustomerEmail());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderBundle::setPaymentMethod
     * @covers \Boodmo\Sales\Entity\OrderBundle::getPaymentMethod
     */
    public function testSetGetPaymentMethod()
    {
        $this->assertEquals($this->orderBundle, $this->orderBundle->setPaymentMethod('test'));
        $this->assertEquals('test', $this->orderBundle->getPaymentMethod());
    }

    public function testSetGetPaymentMethods()
    {
        $this->orderBundle->setPaymentMethod('test');
        $this->assertEquals(['test'], $this->orderBundle->getPaymentMethods());
        $this->orderBundle->setPaymentMethod('test,test2');
        $this->assertEquals(['test', 'test2'], $this->orderBundle->getPaymentMethods());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderBundle::setCheckoutAsGuest
     * @covers \Boodmo\Sales\Entity\OrderBundle::getCheckoutAsGuest
     * @covers \Boodmo\Sales\Entity\OrderBundle::isCheckoutAsGuest
     */
    public function testSetGetCheckoutAsGuest()
    {
        $this->assertEquals(true, $this->orderBundle->getCheckoutAsGuest());
        $this->assertEquals($this->orderBundle, $this->orderBundle->setCheckoutAsGuest(false));
        $this->assertEquals(false, $this->orderBundle->getCheckoutAsGuest());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderBundle::setCurrencyRate
     * @covers \Boodmo\Sales\Entity\OrderBundle::getCurrencyRate
     */
    public function testSetGetCurrencyRate()
    {
        $this->assertEquals($this->orderBundle, $this->orderBundle->setCurrencyRate(5.5));
        $this->assertEquals(5.5, $this->orderBundle->getCurrencyRate());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderBundle::getItemsCount
     */
    public function testGetItemsCount()
    {
        $this->assertEquals(0, $this->orderBundle->getItemsCount());
        $this->initBundle($this->orderBundle);
        $this->assertEquals(3, $this->orderBundle->getItemsCount());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderBundle::getDeliveryTotal
     */
    public function testGetDeliveryTotal()
    {
        $this->initBundle($this->orderBundle);
        $this->assertEquals(500, $this->orderBundle->getDeliveryTotal('USD'));
        $this->assertEquals(0, $this->orderBundle->getDeliveryTotal('INR'));
        $this->assertEquals(5000, $this->orderBundle->getBaseDeliveryTotal());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderBundle::getGrandTotal
     */
    public function testGetGrandTotal()
    {
        $this->initBundle($this->orderBundle);
        $this->assertEquals(500, $this->orderBundle->getGrandTotal('USD'));
        $this->assertEquals(0, $this->orderBundle->getGrandTotal('INR'));
        $this->assertEquals(5000, $this->orderBundle->getBaseGrandTotal());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderBundle::setPackages
     * @covers \Boodmo\Sales\Entity\OrderBundle::addPackage
     * @covers \Boodmo\Sales\Entity\OrderBundle::getPackages
     */
    public function testSetGetPackages()
    {
        $package = new OrderPackage();
        $package->setCurrency('INR');
        $package->setId(1);
        $package->setNumber(123);
        $package2 = new OrderPackage();
        $package2->setCurrency('INR');
        $package2->setId(2);
        $package2->setNumber(1234);
        $package3 = new OrderPackage();
        $package3->setCurrency('USD');
        $package3->setId(3);
        $package3->setNumber(12345);
        $collection = new ArrayCollection();
        $collection->add($package);
        $collection->add($package2);
        $this->assertEquals($this->orderBundle, $this->orderBundle->setPackages($collection));
        $this->assertEquals($collection, $this->orderBundle->getPackages());
        $collection->add($package3);
        $this->assertEquals($this->orderBundle, $this->orderBundle->addPackage($package3));
        $this->assertEquals($collection, $this->orderBundle->getPackages());
        $this->assertEquals(2, $this->orderBundle->getPackagesWithCurrency('INR')->count());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderBundle::setClientIp
     * @covers \Boodmo\Sales\Entity\OrderBundle::getClientIp
     */
    public function testSetGetClientIp()
    {
        $this->assertEquals($this->orderBundle, $this->orderBundle->setClientIp("127.0.0.1"));
        $this->assertEquals("127.0.0.1", $this->orderBundle->getClientIp());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderBundle::setAffiliate
     * @covers \Boodmo\Sales\Entity\OrderBundle::getAffiliate
     */
    public function testSetGetAffiliate()
    {
        $this->assertEquals($this->orderBundle, $this->orderBundle->setAffiliate("processing"));
        $this->assertEquals("processing", $this->orderBundle->getAffiliate());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderBundle::getSubTotal
     */
    public function testGetSubTotal()
    {
        $this->initBundle($this->orderBundle);
        $this->assertEquals(0, $this->orderBundle->getSubTotal('USD'));
        $this->assertEquals(0, $this->orderBundle->getSubTotal('INR'));
        $this->assertEquals(0, $this->orderBundle->getBaseSubTotal());
    }

    public function testGetDiscountTotal()
    {
        $this->initBundle($this->orderBundle);
        $this->assertEquals(0, $this->orderBundle->getDiscountTotal('USD'));
        $this->assertEquals(0, $this->orderBundle->getDiscountTotal('INR'));
        $this->assertEquals(0, $this->orderBundle->getBaseDiscountTotal());
    }

    public function testGetChildren()
    {
        $this->assertEquals(0, $this->orderBundle->getChildren()->count());
    }

    public function testSetGetGaCid()
    {
        $this->assertEquals($this->orderBundle, $this->orderBundle->setGaCid("test"));
        $this->assertEquals("test", $this->orderBundle->getGaCid());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderBundle::setCreditMemos
     * @covers \Boodmo\Sales\Entity\OrderBundle::getCreditMemos
     * @covers \Boodmo\Sales\Entity\OrderBundle::addCreditMemo
     */
    public function testSetGetCreditMemos()
    {
        $credit = new CreditMemo();
        $collection = new ArrayCollection([$credit]);
        $this->assertEquals($this->orderBundle, $this->orderBundle->setCreditMemos($collection));
        $this->assertEquals($collection, $this->orderBundle->getCreditMemos());
        $credit2 = new CreditMemo();
        $this->orderBundle->addCreditMemo($credit2);
        $this->assertEquals(2, $this->orderBundle->getCreditMemos()->count());
        $this->assertEquals($this->orderBundle, $credit2->getBundle());
    }

    public function testGetPaymentMethods()
    {
        $methods = ['cash', 'razor', 'checkout'];
        $this->orderBundle->setPaymentMethod(implode(',', $methods));
        $this->assertEquals($methods, $this->orderBundle->getPaymentMethods());
    }

    public function testGetNumber()
    {
        $this->orderBundle->setCreatedAt(new \DateTime('2017-04-20'))
            ->setId(1234);
        $this->assertEquals('2004/801234', $this->orderBundle->getNumber());
    }

    public function testGetParent()
    {
        $this->assertNull($this->orderBundle->getParent());
    }

    public function testGetBaseDeliveryTotal()
    {
        $this->initBundle($this->orderBundle);
        $this->assertEquals(5000, $this->orderBundle->getBaseDeliveryTotal());
    }

    public function testGetBaseGrandTotal()
    {
        $this->initBundle($this->orderBundle);
        $this->assertEquals(5000, $this->orderBundle->getBaseGrandTotal());
    }

    public function testGetGrandTotalList()
    {
        $this->assertEquals([], $this->orderBundle->getGrandTotalList());

        $this->initBundle($this->orderBundle);
        $this->assertEquals(
            ['USD' => new Money(500, new Currency('USD'))],
            $this->orderBundle->getGrandTotalList()
        );

        $package = (new OrderPackage())->setCurrency('INR');
        $item = (new OrderItem())->setPrice(10025)->setDeliveryPrice(15025)->setQty(1);
        $package->addItem($item);
        $this->orderBundle->addPackage($package);
        $this->assertEquals(
            ['USD' => new Money(500, new Currency('USD')), 'INR' => new Money(25050, new Currency('INR'))],
            $this->orderBundle->getGrandTotalList()
        );
    }

    public function testGetSubTotalList()
    {
        $this->assertEquals([], $this->orderBundle->getSubTotalList());

        $this->initBundle($this->orderBundle);
        $this->assertEquals(
            ['USD' => new Money(0, new Currency('USD'))],
            $this->orderBundle->getSubTotalList()
        );

        $package = (new OrderPackage())->setCurrency('INR');
        $item = (new OrderItem())->setPrice(10025)->setQty(1);
        $package->addItem($item);
        $this->orderBundle->addPackage($package);
        $this->assertEquals(
            ['USD' => new Money(0, new Currency('USD')), 'INR' => new Money(10025, new Currency('INR'))],
            $this->orderBundle->getSubTotalList()
        );
    }

    public function testGetDeliveryTotalList()
    {
        $this->assertEquals([], $this->orderBundle->getDeliveryTotalList());

        $this->initBundle($this->orderBundle);
        $this->assertEquals(
            ['USD' => new Money(500, new Currency('USD'))],
            $this->orderBundle->getDeliveryTotalList()
        );

        $package = (new OrderPackage())->setCurrency('INR');
        $item = (new OrderItem())->setPrice(10025)->setDeliveryPrice(15025)->setQty(1);
        $package->addItem($item);
        $this->orderBundle->addPackage($package);
        $this->assertEquals(
            ['USD' => new Money(500, new Currency('USD')), 'INR' => new Money(15025, new Currency('INR'))],
            $this->orderBundle->getDeliveryTotalList()
        );
    }

    protected function getPaymentInfo(Payment $payment): string
    {
        return 't='.$payment->getTransactionId().';'
            .'total='.$payment->getTotal().';'
            .'base='.$payment->getBaseTotal().';'
            .'currency='.$payment->getCurrency().';';
    }

    protected function initBundle(OrderBundle $orderBundle): void
    {
        $this->package = new OrderPackage();
        $this->package->setCurrency('USD');
        $orderBundle->addPackage($this->package);
        $this->item = new OrderItem();
        $this->item2 = new OrderItem();
        $this->item->setQty(1);
        $this->item2->setQty(2);
        $this->item->setDeliveryPrice(100);
        $this->item->setBaseDeliveryPrice(1000);
        $this->item2->setDeliveryPrice(200);
        $this->item2->setBaseDeliveryPrice(2000);
        $this->package->addItem($this->item)
            ->addItem($this->item2);
    }
}
