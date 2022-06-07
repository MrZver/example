<?php

namespace Boodmo\SalesTest\Entity;

use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Entity\OrderPaymentApplied;
use Boodmo\Sales\Entity\Payment;
use Boodmo\User\Entity\UserProfile\Customer;
use Doctrine\Common\Collections\ArrayCollection;
use Ramsey\Uuid\Uuid;

class PaymentTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Payment
     */
    private $payment;

    /**
     * @var \ReflectionProperty
     */
    private $paymentsAppliedProperty;

    public function setUp()
    {
        $this->payment = new Payment();
        $reflector = new \ReflectionObject($this->payment);
        $this->paymentsAppliedProperty = $reflector->getProperty('paymentsApplied');
        $this->paymentsAppliedProperty->setAccessible(true);
    }

    public function testGetSetId()
    {
        $this->assertTrue(Uuid::isValid($this->payment->getId()));
        $this->assertEquals($this->payment, $this->payment->setId('9e68b55c-d002-4047-bb0c-ac45a19cb8ec'));
        $this->assertEquals('9e68b55c-d002-4047-bb0c-ac45a19cb8ec', $this->payment->getId());
        $this->expectException(\InvalidArgumentException::class);
        $this->payment->setId(123);
    }
    
    public function testSetGetTransactionId()
    {
        $this->assertEquals($this->payment, $this->payment->setTransactionId('123456'));
        $this->assertEquals('123456', $this->payment->getTransactionId());
    }

    public function testSetGetTotal()
    {
        $this->assertEquals($this->payment, $this->payment->setTotal(150));
        $this->assertEquals($this->payment, $this->payment->setBaseTotal(150));
        $this->assertEquals(150, $this->payment->getTotal());
        $this->assertEquals(150, $this->payment->getBaseTotal());
    }

    public function testGetTotalMoney()
    {
        $this->payment->setTotal(150);
        $this->payment->setBaseTotal(1500);
        $this->payment->setCurrency('USD');
        $money = $this->payment->getTotalMoney();
        $moneyBase = $this->payment->getBaseTotalMoney();
        $this->assertEquals('150', $money->getAmount());
        $this->assertEquals('USD', $money->getCurrency()->getCode());
        $this->assertEquals('1500', $moneyBase->getAmount());
        $this->assertEquals('INR', $moneyBase->getCurrency()->getCode());
    }

    public function testSetGetPaymentMethod()
    {
        $this->assertEquals($this->payment, $this->payment->setPaymentMethod('test'));
        $this->assertEquals('test', $this->payment->getPaymentMethod());
    }

    public function testSetGetCurrency()
    {
        $this->assertEquals($this->payment, $this->payment->setCurrency('USD'));
        $this->assertEquals('USD', $this->payment->getCurrency());
    }

    public function testGetCurrencyRate()
    {
        $this->payment->setTotal(150);
        $this->payment->setBaseTotal(1500);
        $this->assertEquals(10, $this->payment->getCurrencyRate());
    }

    public function testSetGetZohoBooksId()
    {
        $this->assertEquals($this->payment, $this->payment->setZohoBooksId('111111'));
        $this->assertEquals('111111', $this->payment->getZohoBooksId());
    }

    public function testSetGetHistoryTrans()
    {
        $history = ['a' => 'b'];
        $this->assertEquals($this->payment, $this->payment->setHistoryTrans($history));
        $this->assertEquals($history, $this->payment->getHistoryTrans());
    }

    public function testGetBills()
    {
        $this->assertEquals(new ArrayCollection(), $this->payment->getBills());

        $bills = new ArrayCollection();
        $bill1 = new OrderBill();
        $bill2 = new OrderBill();
        $bills->add($bill1);
        $bills->add($bill2);

        $orderPaymentApplied = new ArrayCollection();
        $orderPaymentApplied->add(
            $this->createConfiguredMock(OrderPaymentApplied::class, ['getBill' => $bill1])
        );
        $orderPaymentApplied->add(
            $this->createConfiguredMock(OrderPaymentApplied::class, ['getBill' => $bill2])
        );
        $orderPaymentApplied->add(
            $this->createConfiguredMock(OrderPaymentApplied::class, ['getBill' => $bill1])
        );
        $this->paymentsAppliedProperty->setValue($this->payment, $orderPaymentApplied);

        $this->assertEquals($bills, $this->payment->getBills());
        $this->assertEquals(2, $this->payment->getBills()->count());
    }

    public function testSetGetCustomerProfile()
    {
        $this->assertNull($this->payment->getCustomerProfile());

        $customer = new Customer();
        $this->assertEquals($this->payment, $this->payment->setCustomerProfile($customer));
        $this->assertEquals($customer, $this->payment->getCustomerProfile());
    }

    public function testGetUsedAmount()
    {
        $this->assertEquals(0, $this->payment->getUsedAmount());

        $orderPaymentApplied1 = $this->createConfiguredMock(OrderPaymentApplied::class, ['getAmount' => 20025]);
        $orderPaymentApplied2 = $this->createConfiguredMock(OrderPaymentApplied::class, ['getAmount' => 20025]);
        $orderPaymentsApplied = new ArrayCollection();
        $orderPaymentsApplied->add($orderPaymentApplied1);
        $orderPaymentsApplied->add($orderPaymentApplied2);
        $this->paymentsAppliedProperty->setValue($this->payment, $orderPaymentsApplied);

        $this->assertEquals(40050, $this->payment->getUsedAmount());
    }

    public function testClone()
    {
        $this->payment->setHistoryTrans(['a' => 'b']);
        $this->payment->setZohoBooksId('111111');
        $this->payment->setTransactionId('123456');
        $this->payment->setTotal(150);
        $newPayment = clone $this->payment;
        $this->assertNotTrue($newPayment->getId() === $this->payment->getId());
        $this->assertEquals('', $newPayment->getZohoBooksId());
        $this->assertEquals('', $newPayment->getTransactionId());
        $this->assertEquals([], $newPayment->getHistoryTrans());
        $this->assertEquals(150, $newPayment->getTotal());
    }

    /**
     * @covers \Boodmo\Sales\Entity\Payment::applyToBill
     */
    public function testApplyToBill()
    {
        $this->payment->setTotal(20050);
        $bill1 = (new OrderBill())->setCurrency('INR')->setTotal(10050);

        $this->payment->applyToBill($bill1, 5025);
        $this->assertEquals(1, $bill1->getPaymentsApplied()->count());
        $this->assertEquals(1, $bill1->getPayments()->count());
        $this->assertTrue($bill1->getPayments()->contains($this->payment));
        $this->assertEquals(5025, $bill1->getPaidAmount());

        $this->payment->applyToBill($bill1, 5020);
        $this->assertEquals(1, $bill1->getPaymentsApplied()->count());
        $this->assertEquals(1, $bill1->getPayments()->count());
        $this->assertTrue($bill1->getPayments()->contains($this->payment));
        $this->assertEquals(10045, $bill1->getPaidAmount());
    }
}
