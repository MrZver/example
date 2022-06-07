<?php

namespace Boodmo\SalesTest\Entity;

use Boodmo\Sales\Entity\CreditPoint;
use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Entity\OrderCreditPointApplied;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class OrderCreditPointAppliedTest extends TestCase
{
    /**
     * @var OrderCreditPointApplied
     */
    private $entity;

    public function setUp()
    {
        $this->entity = new OrderCreditPointApplied();
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderCreditPointApplied::make
     */
    public function testMake()
    {
        $bill = (new OrderBill())->setCurrency('INR');
        $creditPoint = (new CreditPoint())->setTotal(10015)->setCurrency('INR');

        $result = $this->entity::make($creditPoint, $bill, 10015);
        $this->assertInstanceOf(OrderCreditPointApplied::class, $result);
        $this->assertEquals(10015, $result->getAmount());
        $this->assertSame($bill, $result->getBill());
        $this->assertSame($creditPoint, $result->getCreditPoint());

        $bill = (new OrderBill())->setCurrency('INR')->setId('9e68b55c-d002-4047-bb0c-ac45a19cb8ec');
        $creditPoint = (new CreditPoint())->setTotal(10015)
            ->setCurrency('USD')
            ->setId('8e68b55c-d002-4047-bb0c-ac45a19cb8e8');
        $this->expectExceptionMessage(
            'Bill (id: 9e68b55c-d002-4047-bb0c-ac45a19cb8ec) & CreditPoint (id: 8e68b55c-d002-4047-bb0c-ac45a19cb8e8) have different currency (INR != USD).'
        );
        $this->entity::make($creditPoint, $bill, 10015);

        $bill = (new OrderBill());
        $creditPoint = (new CreditPoint())->setTotal(10015)->setId('8e68b55c-d002-4047-bb0c-ac45a19cb8e8');
        $this->expectExceptionMessage(
            'Unused amount in creditPoint (id: 8e68b55c-d002-4047-bb0c-ac45a19cb8e8) too small (10015 < 20015).'
        );
        $this->entity::make($creditPoint, $bill, 20015);
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderCreditPointApplied::setId
     * @covers \Boodmo\Sales\Entity\OrderCreditPointApplied::getId
     * @covers \Boodmo\Sales\Entity\OrderCreditPointApplied::__construct
     */
    public function testSetGetId()
    {
        $id = Uuid::uuid4();
        $this->assertEquals($this->entity, $this->entity->setId($id));
        $this->assertEquals($id, $this->entity->getId());

        $this->expectExceptionMessage('ID must have uuid4 format.');
        $this->entity->setId('ddd');
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderCreditPointApplied::hash
     */
    public function testHash()
    {
        $this->entity->setCreditPoint((new CreditPoint())->setId('9e68b55c-d002-4047-bb0c-ac45a19cb8ec'));
        $this->entity->setBill((new OrderBill())->setId('8e68b55c-d002-4047-bb0c-ac45a19cb8e8'));
        $this->assertEquals(
            md5('9e68b55c-d002-4047-bb0c-ac45a19cb8ec'.'8e68b55c-d002-4047-bb0c-ac45a19cb8e8'),
            $this->entity->hash()
        );
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderCreditPointApplied::setCreditPoint
     * @covers \Boodmo\Sales\Entity\OrderCreditPointApplied::getCreditPoint
     */
    public function testSetGetCreditPoint()
    {
        $creditPoint = new CreditPoint();
        $this->entity->setCreditPoint($creditPoint);
        $this->assertSame($creditPoint, $this->entity->getCreditPoint());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderCreditPointApplied::setBill
     * @covers \Boodmo\Sales\Entity\OrderCreditPointApplied::getBill
     */
    public function testSetGetBill()
    {
        $orderBill = new OrderBill();
        $this->entity->setBill($orderBill);
        $this->assertSame($orderBill, $this->entity->getBill());
    }

    /**
     * @covers \Boodmo\Sales\Entity\OrderCreditPointApplied::setAmount
     * @covers \Boodmo\Sales\Entity\OrderCreditPointApplied::getAmount
     */
    public function testSetGetAmount()
    {
        /* @var CreditPoint|\PHPUnit_Framework_MockObject_MockObject $creditPoint*/
        $creditPoint = $this->createPartialMock(CreditPoint::class, ['getUnusedAmount', 'getId']);
        $creditPoint->method('getId')->willReturn('9e68b55c-d002-4047-bb0c-ac45a19cb8ec');

        $this->assertEmpty($this->entity->getAmount());

        $creditPoint->method('getUnusedAmount')->willReturn(10015);
        $this->entity->setCreditPoint($creditPoint);
        $this->entity->setAmount(10015);
        $this->assertEquals(10015, $this->entity->getAmount());

        $this->expectExceptionMessage(
            'Unused amount in creditPoint (id: 9e68b55c-d002-4047-bb0c-ac45a19cb8ec) too small (10015 < 20015).'
        );
        $creditPoint->method('getUnusedAmount')->willReturn(10015);
        $this->entity->setCreditPoint($creditPoint);
        $this->entity->setAmount(20015);
    }
}
