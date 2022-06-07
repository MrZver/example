<?php

namespace Boodmo\SalesTest\Model\Checkout;

use Boodmo\Sales\Model\Checkout\ShoppingCart;
use Boodmo\Sales\Model\Delivery;
use Boodmo\Sales\Model\Offer;
use Boodmo\Sales\Model\Product;
use Boodmo\Sales\Model\Seller;
use Boodmo\User\Model\AddressBook;
use Money\Money;
use Money\Currency;
use PHPUnit\Framework\TestCase;
use Zend\Stdlib\ArrayObject;
use Doctrine\Common\Collections\ArrayCollection;

class ShoppingCartTest extends TestCase
{
    /**
     * @var ShoppingCart
     */
    private $shoppingCart;

    /**
     * @var Offer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $offer1;

    /**
     * @var Offer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $offer2;

    /**
     * @var Offer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $offer3;

    /**
     * @var Product|\PHPUnit_Framework_MockObject_MockObject
     */
    private $product1;

    /**
     * @var Product|\PHPUnit_Framework_MockObject_MockObject
     */
    private $product2;

    /**
     * @var Product|\PHPUnit_Framework_MockObject_MockObject
     */
    private $product3;

    /**
     * @var Seller
     */
    private $seller1;

    /**
     * @var Seller
     */
    private $seller2;

    /**
     * @var Seller
     */
    private $seller3;

    /**
     * @var Delivery|\PHPUnit_Framework_MockObject_MockObject
     */
    private $delivery1;

    /**
     * @var Delivery|\PHPUnit_Framework_MockObject_MockObject
     */
    private $delivery2;

    /**
     * @var Delivery|\PHPUnit_Framework_MockObject_MockObject
     */
    private $delivery3;

    /**
     * @var ArrayObject
     */
    private $storage;

    public function setUp()
    {
        $this->seller1  = new Seller(1, 'seller1', 1, '', '', '', true);
        $this->seller2  = new Seller(2, 'seller2', 2, '', '', '', true);
        $this->seller3  = new Seller(3, 'seller3', 3, '', '', '', true);

        $this->delivery1  = $this->createConfiguredMock(
            Delivery::class,
            ['getBasePrice' => new Money(100, new Currency('INR')), 'toArray' => ['id' => 1]]
        );
        $this->delivery2  = $this->createConfiguredMock(
            Delivery::class,
            ['getBasePrice' => new Money(300, new Currency('INR')), 'toArray' => ['id' => 2]]
        );
        $this->delivery3  = $this->createConfiguredMock(
            Delivery::class,
            ['getBasePrice' => new Money(500, new Currency('INR')), 'toArray' => ['id' => 3]]
        );

        $this->product1 = $this->createConfiguredMock(
            Product::class,
            [
                'getId' => 1,
                'getSeller' => $this->seller1,
                'getRequestedQty' => 1,
                'getBasePrice' => new Money(100, new Currency('INR')),
                'toArray' => ['id' => 1],
            ]
        );
        $this->product2 = $this->createConfiguredMock(
            Product::class,
            [
                'getId' => 2,
                'getSeller' => $this->seller2,
                'getRequestedQty' => 3,
                'getBasePrice' => new Money(300, new Currency('INR')),
                'toArray' => ['id' => 2],
            ]
        );
        $this->product3 = $this->createConfiguredMock(
            Product::class,
            [
                'getId' => 3,
                'getSeller' => $this->seller3,
                'getRequestedQty' => 5,
                'getBasePrice' => new Money(500, new Currency('INR')),
                'toArray' => ['id' => 3],
            ]
        );

        $this->offer1 = $this->createPartialMock(Offer::class, ['getProduct', 'getDelivery']);
        $this->offer2 = $this->createPartialMock(Offer::class, ['getProduct', 'getDelivery']);
        $this->offer3 = $this->createPartialMock(Offer::class, ['getProduct', 'getDelivery']);
        $this->offer1->method('getProduct')->willReturn($this->product1);
        $this->offer2->method('getProduct')->willReturn($this->product2);
        $this->offer3->method('getProduct')->willReturn($this->product3);
        $this->offer1->method('getDelivery')->willReturn($this->delivery1);
        $this->offer2->method('getDelivery')->willReturn($this->delivery2);
        $this->offer3->method('getDelivery')->willReturn($this->delivery3);

        $this->storage = new ArrayObject([
            ShoppingCart::STORAGE_KEY_ITEMS => [1 => $this->offer1, 2 => $this->offer2]
        ]);

        $this->shoppingCart = new ShoppingCart([1 => $this->offer1, 2 => $this->offer2], $this->storage);
    }

    public function testGetOffers()
    {
        $this->assertInstanceOf(ArrayCollection::class, $this->shoppingCart->getOffers());
    }

    public function testExistsOffer()
    {
        $this->assertTrue($this->shoppingCart->existsOffer($this->offer1));
        $this->assertFalse($this->shoppingCart->existsOffer($this->offer3));
    }

    public function testIsEmpty()
    {
        $this->assertFalse($this->shoppingCart->isEmpty());

        $this->shoppingCart->removeOffer($this->offer1);
        $this->shoppingCart->removeOffer($this->offer2);
        $this->assertTrue($this->shoppingCart->isEmpty());
    }

    public function testAddOffer()
    {
        //add exist offer
        $this->shoppingCart->addOffer($this->offer2);
        $this->assertEquals(2, $this->shoppingCart->getOffers()->count());

        //add new offer
        $this->shoppingCart->addOffer($this->offer3);
        $this->assertEquals(3, $this->shoppingCart->getOffers()->count());
    }

    public function testRemoveOffer()
    {
        //remove unexist offer
        $this->shoppingCart->removeOffer($this->offer3);
        $this->assertEquals(2, $this->shoppingCart->getOffers()->count());

        //remove exist offer
        $this->shoppingCart->removeOffer($this->offer2);
        $this->assertEquals(1, $this->shoppingCart->getOffers()->count());
    }

    public function testClearAll()
    {
        $this->shoppingCart->clearAll(1);
        $this->assertTrue($this->shoppingCart->isEmpty());
    }

    public function testGetListSeller()
    {
        $this->assertEquals([$this->seller1, $this->seller2], $this->shoppingCart->getListSeller());
    }

    public function testGetTotalCountItems()
    {
        $this->assertEquals(4, $this->shoppingCart->getTotalCountItems());

        $this->shoppingCart->addOffer($this->offer3);
        $this->assertEquals(9, $this->shoppingCart->getTotalCountItems());
    }

    public function testGetTotalItems()
    {
        $this->assertEquals(2, $this->shoppingCart->getTotalItems());

        $this->shoppingCart->addOffer($this->offer3);
        $this->assertEquals(3, $this->shoppingCart->getTotalItems());
    }

    public function testGetBaseSubTotal()
    {
        $this->assertEquals(new Money(1000, new Currency('INR')), $this->shoppingCart->getBaseSubTotal());

        $this->shoppingCart->addOffer($this->offer3);
        $this->assertEquals(new Money(3500, new Currency('INR')), $this->shoppingCart->getBaseSubTotal());
    }

    public function testGetBaseDeliveryTotal()
    {
        $this->assertEquals(new Money(1000, new Currency('INR')), $this->shoppingCart->getBaseDeliveryTotal());

        $this->shoppingCart->addOffer($this->offer3);
        $this->assertEquals(new Money(3500, new Currency('INR')), $this->shoppingCart->getBaseDeliveryTotal());
    }

    public function testGetBaseGrandTotall()
    {
        $this->assertEquals(new Money(2000, new Currency('INR')), $this->shoppingCart->getBaseGrandTotal());

        $this->shoppingCart->addOffer($this->offer3);
        $this->assertEquals(new Money(7000, new Currency('INR')), $this->shoppingCart->getBaseGrandTotal());
    }

    public function testNextStep()
    {
        $this->assertEquals('', $this->shoppingCart->nextStep(''));
        $this->assertEquals(ShoppingCart::STEP_EMAIL, $this->shoppingCart->nextStep(ShoppingCart::STEP_CART));
        $this->assertEquals(ShoppingCart::STEP_ADDRESS, $this->shoppingCart->nextStep(ShoppingCart::STEP_EMAIL));
        $this->assertEquals(ShoppingCart::STEP_REVIEW, $this->shoppingCart->nextStep(ShoppingCart::STEP_ADDRESS));
        $this->assertEquals(ShoppingCart::STEP_PAYMENT, $this->shoppingCart->nextStep(ShoppingCart::STEP_REVIEW));
        $this->assertEquals('', $this->shoppingCart->nextStep(ShoppingCart::STEP_PAYMENT));
        $this->assertEquals('', $this->shoppingCart->nextStep('test'));
    }

    public function testGetStep()
    {
        $this->assertEquals(ShoppingCart::STEP_CART, $this->shoppingCart->getStep());

        $this->shoppingCart->setStep($this->shoppingCart->nextStep(ShoppingCart::STEP_CART));
        $this->assertEquals(ShoppingCart::STEP_EMAIL, $this->shoppingCart->getStep());

        $this->shoppingCart->setStep($this->shoppingCart->nextStep(ShoppingCart::STEP_EMAIL));
        $this->assertEquals(ShoppingCart::STEP_ADDRESS, $this->shoppingCart->getStep());

        $this->shoppingCart->setStep($this->shoppingCart->nextStep(ShoppingCart::STEP_ADDRESS));
        $this->assertEquals(ShoppingCart::STEP_REVIEW, $this->shoppingCart->getStep());

        $this->shoppingCart->setStep($this->shoppingCart->nextStep(ShoppingCart::STEP_REVIEW));
        $this->assertEquals(ShoppingCart::STEP_PAYMENT, $this->shoppingCart->getStep());

        $this->shoppingCart->setStep($this->shoppingCart->nextStep(ShoppingCart::STEP_PAYMENT));
        $this->assertEquals(ShoppingCart::STEP_PAYMENT, $this->shoppingCart->getStep());

        $this->shoppingCart->setStep('test');
        $this->assertEquals(ShoppingCart::STEP_PAYMENT, $this->shoppingCart->getStep());
    }

    public function testGetStepIndexByName()
    {
        $this->assertEquals(0, $this->shoppingCart->getStepIndexByName(ShoppingCart::STEP_CART));
        $this->assertEquals(1, $this->shoppingCart->getStepIndexByName(ShoppingCart::STEP_EMAIL));
        $this->assertEquals(2, $this->shoppingCart->getStepIndexByName(ShoppingCart::STEP_ADDRESS));
        $this->assertEquals(3, $this->shoppingCart->getStepIndexByName(ShoppingCart::STEP_REVIEW));
        $this->assertEquals(4, $this->shoppingCart->getStepIndexByName(ShoppingCart::STEP_PAYMENT));
        $this->assertEquals(-1, $this->shoppingCart->getStepIndexByName('test'));
    }

    /**
     * @covers \Boodmo\Sales\Model\Checkout\ShoppingCart::getEmail
     * @covers \Boodmo\Sales\Model\Checkout\ShoppingCart::setEmail
     */
    public function testGetSetEmail()
    {
        $this->assertEquals(null, $this->shoppingCart->getEmail());
        $this->shoppingCart->setEmail('test');
        $this->assertEquals('test', $this->shoppingCart->getEmail());
        $this->shoppingCart->setEmail('test@test.com');
        $this->assertEquals('test@test.com', $this->shoppingCart->getEmail());
        $this->shoppingCart->setEmail('');
        $this->assertEquals('', $this->shoppingCart->getEmail());
    }

    /**
     * @covers \Boodmo\Sales\Model\Checkout\ShoppingCart::getAddress
     * @covers \Boodmo\Sales\Model\Checkout\ShoppingCart::setAddress
     */
    public function testGetSetAddress()
    {
        $this->assertEquals(AddressBook::fromData([]), $this->shoppingCart->getAddress());

        $addressBook = new AddressBook(
            'country',
            'state',
            'city',
            'address',
            '123123',
            '12345678901',
            'first_name',
            'last_name'
        );
        $this->shoppingCart->setAddress($addressBook);
        $this->assertEquals($addressBook, $this->shoppingCart->getAddress());
    }

    /**
     * @covers \Boodmo\Sales\Model\Checkout\ShoppingCart::getPaymentMethods
     * @covers \Boodmo\Sales\Model\Checkout\ShoppingCart::setPaymentMethods
     */
    public function testGetSetPaymentMethods()
    {
        $this->assertEquals([], $this->shoppingCart->getPaymentMethods());

        $this->shoppingCart->setPaymentMethods(['payment1', 'payment2']);
        $this->assertEquals(['payment1', 'payment2'], $this->shoppingCart->getPaymentMethods());
    }

    public function testIsReadyForOrder()
    {
        //only with offers
        $this->assertFalse($this->shoppingCart->isReadyForOrder());

        //with offers, email, address
        $this->shoppingCart->setEmail('test@test.com');
        $this->shoppingCart->setAddress(new AddressBook(
            'country',
            'state',
            'city',
            'address',
            '123123',
            '12345678901',
            'first_name',
            'last_name'
        ));
        $this->assertTrue($this->shoppingCart->isReadyForOrder());

        //only with address, email
        $this->shoppingCart->clearAll(1);
        $this->assertFalse($this->shoppingCart->isReadyForOrder());
    }

    public function testToArray()
    {
        $this->assertEquals(
            [
                1 => ['product' => ['id' => 1], 'delivery' => ['id' => 1]],
                2 => ['product' => ['id' => 2], 'delivery' => ['id' => 2]]
            ],
            $this->shoppingCart->toArray()
        );

        $this->shoppingCart->addOffer($this->offer3);
        $this->assertEquals(
            [
                1 => ['product' => ['id' => 1], 'delivery' => ['id' => 1]],
                2 => ['product' => ['id' => 2], 'delivery' => ['id' => 2]],
                3 => ['product' => ['id' => 3], 'delivery' => ['id' => 3]]
            ],
            $this->shoppingCart->toArray()
        );

        $this->shoppingCart->clearAll(1);
        $this->assertEquals([], $this->shoppingCart->toArray());
    }

    public function testGetCurrentStepName()
    {
        $this->assertEquals('Cart', $this->shoppingCart->getCurrentStepName());

        $this->shoppingCart->setStep($this->shoppingCart->nextStep(ShoppingCart::STEP_CART));
        $this->assertEquals('Email', $this->shoppingCart->getCurrentStepName());

        $this->shoppingCart->setStep($this->shoppingCart->nextStep(ShoppingCart::STEP_EMAIL));
        $this->assertEquals('Delivery Address', $this->shoppingCart->getCurrentStepName());

        $this->shoppingCart->setStep($this->shoppingCart->nextStep(ShoppingCart::STEP_ADDRESS));
        $this->assertEquals('Review Order', $this->shoppingCart->getCurrentStepName());

        $this->shoppingCart->setStep($this->shoppingCart->nextStep(ShoppingCart::STEP_REVIEW));
        $this->assertEquals('Make Payment', $this->shoppingCart->getCurrentStepName());

        $this->shoppingCart->setStep($this->shoppingCart->nextStep(ShoppingCart::STEP_PAYMENT));
        $this->assertEquals('Make Payment', $this->shoppingCart->getCurrentStepName());

        $this->shoppingCart->setStep('test');
        $this->assertEquals('Make Payment', $this->shoppingCart->getCurrentStepName());
    }
}
