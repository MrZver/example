<?php

namespace Boodmo\SalesTest\Entity;

use Boodmo\Sales\Entity\Cart as Entity;
use Boodmo\User\Entity\User;
use Ramsey\Uuid\Uuid;

class CartTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Entity
     */
    private $cart;

    public function setUp()
    {
        $cart = new Entity();
        $this->cart = $cart;
    }

    public function testSetGetId()
    {
        $uuid = Uuid::uuid4()->toString();
        $this->assertEquals($this->cart, $this->cart->setId($uuid));
        $this->assertEquals($uuid, $this->cart->getId());
    }

    public function testSetGetUser()
    {
        $user = new User();
        $this->assertEquals($this->cart, $this->cart->setUser($user));
        $this->assertEquals($user, $this->cart->getUser());
    }

    public function testSetGetItems()
    {
        $items = ['123' => 2];
        $this->assertEquals($this->cart, $this->cart->setItems($items));
        $this->assertEquals($items, $this->cart->getItems());
    }

    public function testSetGetStep()
    {
        $this->assertEquals($this->cart, $this->cart->setStep('test'));
        $this->assertEquals('test', $this->cart->getStep());
    }

    public function testSetGetScope()
    {
        $this->assertEquals($this->cart, $this->cart->setScope('test'));
        $this->assertEquals('test', $this->cart->getScope());
    }

    public function testSetGetSessionId()
    {
        $this->assertEquals($this->cart, $this->cart->setSessionId('test'));
        $this->assertEquals('test', $this->cart->getSessionId());
    }

    public function testSetGetEmail()
    {
        $this->assertEquals($this->cart, $this->cart->setEmail('test@test.com'));
        $this->assertEquals('test@test.com', $this->cart->getEmail());
    }

    public function testSetGetOrderId()
    {
        $this->assertEquals($this->cart, $this->cart->setOrderId(1234));
        $this->assertEquals(1234, $this->cart->getOrderId());
    }

    public function testSetGetPayment()
    {
        $payment = ['123' => 2];
        $this->assertEquals($this->cart, $this->cart->setPayment($payment));
        $this->assertEquals($payment, $this->cart->getPayment());
    }

    public function testSetGetAddress()
    {
        $address = ['123' => 2];
        $this->assertEquals($this->cart, $this->cart->setAddress($address));
        $this->assertEquals($address, $this->cart->getAddress());
    }

    public function testClear()
    {
        $this->cart->setAddress(['123' => 2])
            ->setEmail('test@test.com')
            ->setStep('test')
            ->setItems([1=>1])
            ->setUser(new User())
            ->setOrderId(1234)
            ->setReminded(new \DateTime());
        $this->cart->clear();
        $this->assertEmpty($this->cart->getReminded());
        $this->assertEmpty($this->cart->getAddress());
        $this->assertEmpty($this->cart->getEmail());
        $this->assertEmpty($this->cart->getStep());
        $this->assertEmpty($this->cart->getItems());
        $this->assertEquals(1234, $this->cart->getOrderId());
        $this->assertInstanceOf(User::class, $this->cart->getUser());
        $this->cart->clear(true);
        $this->assertEmpty($this->cart->getOrderId());
        $this->assertInstanceOf(User::class, $this->cart->getUser());
    }

    public function testSetGetReminded()
    {
        $this->assertEmpty($this->cart->getReminded());
        $date = new \DateTimeImmutable();
        $this->assertEquals($this->cart, $this->cart->setReminded($date));
        $this->assertEquals($date, $this->cart->getReminded());
    }
}
