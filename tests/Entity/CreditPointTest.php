<?php

namespace Boodmo\SalesTest\Entity;

use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Entity\CreditPoint;
use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Entity\OrderCreditPointApplied;
use Boodmo\User\Entity\UserProfile\Customer;
use Money\Currency;
use Money\Money;
use Doctrine\Common\Collections\ArrayCollection;
use Ramsey\Uuid\Uuid;

class CreditPointTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CreditPoint
     */
    private $entity;

    /**
     * @var \ReflectionProperty
     */
    private $creditPointsAppliedProperty;

    public function setUp()
    {
        $this->entity = new CreditPoint();
        $reflector = new \ReflectionObject($this->entity);
        $this->creditPointsAppliedProperty = $reflector->getProperty('creditPointsApplied');
        $this->creditPointsAppliedProperty->setAccessible(true);
    }

    public function testGetSetId()
    {
        $this->assertTrue(Uuid::isValid($this->entity->getId()));
        $this->assertEquals($this->entity, $this->entity->setId('9e68b55c-d002-4047-bb0c-ac45a19cb8ec'));
        $this->assertEquals('9e68b55c-d002-4047-bb0c-ac45a19cb8ec', $this->entity->getId());
        $this->expectException(\InvalidArgumentException::class);
        $this->entity->setId(123);
    }

    public function testSetGetTotal()
    {
        $this->assertEquals(0, $this->entity->getTotal());
        $this->assertEquals(0, $this->entity->getBaseTotal());
        $this->assertEquals($this->entity, $this->entity->setTotal(15025));
        $this->assertEquals($this->entity, $this->entity->setBaseTotal(25025));
        $this->assertEquals(15025, $this->entity->getTotal());
        $this->assertEquals(25025, $this->entity->getBaseTotal());
    }

    public function testGetTotalMoney()
    {
        $this->assertEquals(new Money(0, new Currency('INR')), $this->entity->getTotalMoney());
        $this->assertEquals(new Money(0, new Currency('INR')), $this->entity->getBaseTotalMoney());

        $this->entity->setTotal(15025);
        $this->entity->setBaseTotal(25025);
        $this->entity->setCurrency('USD');

        $this->assertEquals(new Money(15025, new Currency('USD')), $this->entity->getTotalMoney());
        $this->assertEquals(new Money(25025, new Currency('INR')), $this->entity->getBaseTotalMoney());
    }

    public function testGetCurrencyRate()
    {
        $this->assertEquals(0, $this->entity->getCurrencyRate());

        $this->entity->setTotal(15025);
        $this->entity->setBaseTotal(187812);
        $this->assertEquals(12.499966722129784, $this->entity->getCurrencyRate());
    }

    public function testSetGetCurrency()
    {
        $this->assertEquals(MoneyService::BASE_CURRENCY, $this->entity->getCurrency());
        $this->assertEquals($this->entity, $this->entity->setCurrency('USD'));
        $this->assertEquals('USD', $this->entity->getCurrency());
    }

    public function testSetGetType()
    {
        $this->assertEquals('', $this->entity->getType());
        $this->assertEquals($this->entity, $this->entity->setType('test_type'));
        $this->assertEquals('test_type', $this->entity->getType());
    }

    public function testSetGetZohoBooksId()
    {
        $this->assertEquals('', $this->entity->getZohoBooksId());
        $this->assertEquals($this->entity, $this->entity->setZohoBooksId('111111'));
        $this->assertEquals('111111', $this->entity->getZohoBooksId());
    }

    public function testSetGetCustomerProfile()
    {
        $this->assertNull($this->entity->getCustomerProfile());

        $customer = new Customer();
        $this->assertEquals($this->entity, $this->entity->setCustomerProfile($customer));
        $this->assertEquals($customer, $this->entity->getCustomerProfile());
    }

    public function testGetCreditPointsApplied()
    {
        $this->assertEquals(new ArrayCollection(), $this->entity->getCreditPointsApplied());
    }

    public function testGetUsedAmount()
    {
        $this->assertEquals(0, $this->entity->getUsedAmount());

        $creditPointApplied1 = $this->createConfiguredMock(OrderCreditPointApplied::class, ['getAmount' => 20025]);
        $creditPointApplied2 = $this->createConfiguredMock(OrderCreditPointApplied::class, ['getAmount' => 20025]);
        $orderCreditPointsApplied = new ArrayCollection();
        $orderCreditPointsApplied->add($creditPointApplied1);
        $orderCreditPointsApplied->add($creditPointApplied2);
        $this->creditPointsAppliedProperty->setValue($this->entity, $orderCreditPointsApplied);

        $this->assertEquals(40050, $this->entity->getUsedAmount());
    }

    public function testGetUnusedAmount()
    {
        $this->assertEquals(0, $this->entity->getUnusedAmount());

        $this->entity->setTotal(100025);
        $creditPointApplied1 = $this->createConfiguredMock(OrderCreditPointApplied::class, ['getAmount' => 20025]);
        $creditPointApplied2 = $this->createConfiguredMock(OrderCreditPointApplied::class, ['getAmount' => 20025]);
        $orderCreditPointsApplied = new ArrayCollection();
        $orderCreditPointsApplied->add($creditPointApplied1);
        $orderCreditPointsApplied->add($creditPointApplied2);
        $this->creditPointsAppliedProperty->setValue($this->entity, $orderCreditPointsApplied);

        $this->assertEquals(59975, $this->entity->getUnusedAmount());
    }

    /**
     * @covers \Boodmo\Sales\Entity\CreditPoint::applyToBill
     */
    public function testApplyToBill()
    {
        $this->entity->setTotal(20050);
        $bill1 = (new OrderBill())->setCurrency('INR')->setTotal(10050);

        $this->entity->applyToBill($bill1, 5025);
        $this->assertEquals(1, $bill1->getCreditPointsApplied()->count());
        $this->assertEquals(1, $bill1->getCreditPoints()->count());
        $this->assertTrue($bill1->getCreditPoints()->contains($this->entity));
        $this->assertEquals(5025, $bill1->getPaidAmount());

        $this->entity->applyToBill($bill1, 5020);
        $this->assertEquals(1, $bill1->getCreditPointsApplied()->count());
        $this->assertEquals(1, $bill1->getCreditPoints()->count());
        $this->assertTrue($bill1->getCreditPoints()->contains($this->entity));
        $this->assertEquals(10045, $bill1->getPaidAmount());
    }

    public function testGetBills()
    {
        $this->assertEquals(new ArrayCollection(), $this->entity->getBills());

        $bills = new ArrayCollection();
        $bill1 = new OrderBill();
        $bill2 = new OrderBill();
        $bills->add($bill1);
        $bills->add($bill2);

        $orderCreditPointsApplied = new ArrayCollection();
        $orderCreditPointsApplied->add(
            $this->createConfiguredMock(OrderCreditPointApplied::class, ['getBill' => $bill1])
        );
        $orderCreditPointsApplied->add(
            $this->createConfiguredMock(OrderCreditPointApplied::class, ['getBill' => $bill2])
        );
        $orderCreditPointsApplied->add(
            $this->createConfiguredMock(OrderCreditPointApplied::class, ['getBill' => $bill1])
        );
        $this->creditPointsAppliedProperty->setValue($this->entity, $orderCreditPointsApplied);

        $this->assertEquals($bills, $this->entity->getBills());
        $this->assertEquals(2, $this->entity->getBills()->count());
    }

    public function testClone()
    {
        $this->entity->setTotal(10025)
            ->setBaseTotal(10026)
            ->setCurrency('INR')
            ->setType('test_type')
            ->setZohoBooksId('12345')
            ->setCustomerProfile((new Customer())->setId(1));
        $orderCreditPointsApplied = new ArrayCollection();
        $orderCreditPointsApplied->add($this->createMock(OrderCreditPointApplied::class));
        $this->creditPointsAppliedProperty->setValue($this->entity, $orderCreditPointsApplied);

        $newEntity = clone $this->entity;
        $this->assertNotSame($this->entity, $newEntity);
        $this->assertNotSame($this->entity->getId(), $newEntity->getId());
        $this->assertEquals(new ArrayCollection(), $newEntity->getCreditPointsApplied());
        $this->assertEquals('', $newEntity->getZohoBooksId());

        $this->assertSame($this->entity->getTotal(), $newEntity->getTotal());
        $this->assertSame($this->entity->getBaseTotal(), $newEntity->getBaseTotal());
        $this->assertSame($this->entity->getBaseTotal(), $newEntity->getBaseTotal());
        $this->assertSame($this->entity->getCurrency(), $newEntity->getCurrency());
        $this->assertSame($this->entity->getType(), $newEntity->getType());
        $this->assertEquals($this->entity->getCustomerProfile(), $newEntity->getCustomerProfile());
    }
}
