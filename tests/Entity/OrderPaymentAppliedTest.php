<?php

namespace Boodmo\SalesTest\Entity;

use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Entity\OrderPaymentApplied;
use Boodmo\Sales\Entity\Payment;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class OrderPaymentAppliedTest extends TestCase
{
    /**
     * @var OrderPaymentApplied
     */
    private $entity;

    /**
     * @var Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $payment;

    /**
     * @var OrderBill|\PHPUnit_Framework_MockObject_MockObject
     */
    private $bill;

    public function setUp()
    {
        $this->bill = $this->createPartialMock(OrderBill::class, ['getPaymentsApplied']);
        $this->payment = $this->createPartialMock(Payment::class, ['getUnusedAmount', 'getPaymentsApplied']);

        $this->payment->method('getUnusedAmount')->willReturn(10025);
        $this->payment->method('getPaymentsApplied')->willReturn(new ArrayCollection());
        $this->bill->method('getPaymentsApplied')->willReturn(new ArrayCollection());

        $this->entity = OrderPaymentApplied::make($this->payment, $this->bill, 5025);
    }

    public function testSetGetId()
    {
        $this->assertTrue(Uuid::isValid($this->entity->getId()));
        $this->assertEquals($this->entity, $this->entity->setId('9e68b55c-d002-4047-bb0c-ac45a19cb8ec'));
        $this->assertEquals('9e68b55c-d002-4047-bb0c-ac45a19cb8ec', $this->entity->getId());
        $this->expectException(\InvalidArgumentException::class);
        $this->entity->setId(123);
    }
}
