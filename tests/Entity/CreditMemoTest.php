<?php

namespace Boodmo\SalesTest\Entity;

use Boodmo\Sales\Entity\CreditMemo;
use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderCreditPointApplied;
use Doctrine\Common\Collections\ArrayCollection;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class CreditMemoTest extends TestCase
{
    /**
     * @var CreditMemo
     */
    private $creditMemo;

    /**
     * @covers \Boodmo\Sales\Entity\CreditMemo::__construct
     */
    public function setUp()
    {
        $this->creditMemo = new CreditMemo();
    }

    /**
     * @covers \Boodmo\Sales\Entity\CreditMemo::setId
     * @covers \Boodmo\Sales\Entity\CreditMemo::getId
     */
    public function testSetGetId()
    {
        $this->assertTrue(Uuid::isValid($this->creditMemo->getId()));
        $this->assertEquals($this->creditMemo, $this->creditMemo->setId("9e68b55c-d002-4047-bb0c-ac45a19cb8ec"));
        $this->assertEquals("9e68b55c-d002-4047-bb0c-ac45a19cb8ec", $this->creditMemo->getId());
        $this->expectException(\InvalidArgumentException::class);
        $this->creditMemo->setId("123");
    }

    /**
     * @covers \Boodmo\Sales\Entity\CreditMemo::setBundle
     * @covers \Boodmo\Sales\Entity\CreditMemo::getBundle
     */
    public function testSetGetBundle()
    {
        $bundle = new OrderBundle();
        $this->assertEquals($this->creditMemo, $this->creditMemo->setBundle($bundle));
        $this->assertEquals($bundle, $this->creditMemo->getBundle());
    }

    /**
     * @covers \Boodmo\Sales\Entity\CreditMemo::setOpen
     * @covers \Boodmo\Sales\Entity\CreditMemo::isOpen
     */
    public function testSetIsOpen()
    {
        $this->assertEquals($this->creditMemo, $this->creditMemo->setOpen(true));
        $this->assertEquals(true, $this->creditMemo->isOpen());
    }

    public function testOpenClosedState()
    {
        $this->creditMemo->setOpen(true);
        $this->assertEquals(null, $this->creditMemo->getClosed());
        $this->creditMemo->setOpen(false);
        $date = $this->creditMemo->getClosed();
        $this->assertInstanceOf(\DateTime::class, $date);
    }

    /**
     * @covers \Boodmo\Sales\Entity\CreditMemo::setTotal
     * @covers \Boodmo\Sales\Entity\CreditMemo::getTotal
     */
    public function testSetGetTotal()
    {
        $this->assertEquals($this->creditMemo, $this->creditMemo->setTotal(1000));
        $this->assertEquals(1000, $this->creditMemo->getTotal());
    }

    public function testSetGetCurrency()
    {
        $this->assertEquals($this->creditMemo, $this->creditMemo->setCurrency('USD'));
        $this->assertEquals('USD', $this->creditMemo->getCurrency());
    }

    public function testSetGetBaseTotal()
    {
        $this->assertEquals($this->creditMemo, $this->creditMemo->setBaseTotal(1000));
        $this->assertEquals(1000, $this->creditMemo->getBaseTotal());
    }

    public function testSetGetClosed()
    {
        $date = new \DateTime();
        $this->assertEquals($this->creditMemo, $this->creditMemo->setClosed($date));
        $this->assertEquals($date, $this->creditMemo->getClosed());
    }

    public function testSetGetCalculatedTotal()
    {
        $this->assertEquals(0, $this->creditMemo->getCalculatedTotal());

        $this->creditMemo->setCalculatedTotal(1000);
        $this->assertEquals(1000, $this->creditMemo->getCalculatedTotal());
    }

    /**
     * @covers \Boodmo\Sales\Entity\CreditMemo::isHasCreditPoints
     */
    public function testIsHasCreditPoints()
    {
        $this->assertFalse($this->creditMemo->isHasCreditPoints());

        $this->creditMemo->setBundle(new OrderBundle());
        $this->assertFalse($this->creditMemo->isHasCreditPoints());

        $this->creditMemo->setBundle(
            $this->createConfiguredMock(
                OrderBundle::class,
                [
                    'getCreditPointsAppliedMoney' => ['INR' => new Money(10025, new Currency('INR'))]
                ]
            )
        );
        $this->creditMemo->setCurrency('USD');
        $this->assertFalse($this->creditMemo->isHasCreditPoints());

        $this->creditMemo->setBundle(
            $this->createConfiguredMock(
                OrderBundle::class,
                [
                    'getCreditPointsAppliedMoney' => ['INR' => new Money(10025, new Currency('INR'))]
                ]
            )
        );
        $this->creditMemo->setCurrency('INR');
        $this->assertTrue($this->creditMemo->isHasCreditPoints());
    }

    public function testGetMoneyTotals()
    {
        $this->creditMemo->setTotal(100);
        $this->creditMemo->setBaseTotal(1000);
        $this->creditMemo->setCurrency('USD');
        $total = $this->creditMemo->getTotalMoney();
        $totalBase = $this->creditMemo->getBaseTotalMoney();
        $this->assertInstanceOf(Money::class, $total);
        $this->assertInstanceOf(Money::class, $totalBase);
        $this->assertEquals('100', $total->getAmount());
        $this->assertEquals('USD', $total->getCurrency()->getCode());
        $this->assertEquals('1000', $totalBase->getAmount());
        $this->assertEquals('INR', $totalBase->getCurrency()->getCode());
    }
}
