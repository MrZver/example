<?php

namespace Boodmo\SalesTest\Entity;

use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderPaymentApplied;
use Boodmo\Sales\Entity\Payment;
use Money\Currency;
use Money\Money;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class OrderBillTest extends TestCase
{
    /**
     * @var OrderBill
     */
    private $entity;

    /**
     * @var \ReflectionProperty
     */
    private $paymentsAppliedProperty;

    public function setUp()
    {
        $this->entity = (new OrderBill())->setId('3839075a-3be1-4596-ae40-f65e8ae0eb29');
        $reflector = new \ReflectionObject($this->entity);
        $this->paymentsAppliedProperty = $reflector->getProperty('paymentsApplied');
        $this->paymentsAppliedProperty->setAccessible(true);
    }

    public function testSetGetId()
    {
        $this->assertTrue(Uuid::isValid($this->entity->getId()));
        $this->assertEquals($this->entity, $this->entity->setId('9e68b55c-d002-4047-bb0c-ac45a19cb8ec'));
        $this->assertEquals('9e68b55c-d002-4047-bb0c-ac45a19cb8ec', $this->entity->getId());
        $this->expectException(\InvalidArgumentException::class);
        $this->entity->setId(123);
    }

    public function testSetGetBundle()
    {
        $this->assertNull($this->entity->getBundle());

        $bundle = (new OrderBundle())->setId(1);
        $this->entity->setBundle($bundle);
        $this->assertSame($bundle, $this->entity->getBundle());
    }

    public function testSetGetPaymentMethod()
    {
        $this->assertNull($this->entity->getPaymentMethod());

        $this->entity->setPaymentMethod('paypal');
        $this->assertSame('paypal', $this->entity->getPaymentMethod());
    }

    public function testSetGetType()
    {
        $this->assertNull($this->entity->getType());

        $this->entity->setType('type');
        $this->assertSame('type', $this->entity->getType());
    }

    public function testSetGetCurrency()
    {
        $this->assertSame(MoneyService::BASE_CURRENCY, $this->entity->getCurrency());

        $this->entity->setCurrency('USD');
        $this->assertSame('USD', $this->entity->getCurrency());
    }

    public function testSetGetHistory()
    {
        $this->assertSame([], $this->entity->getHistory());

        $this->entity->setHistory(['test' => 1]);
        $this->assertSame(['test' => 1], $this->entity->getHistory());
    }

    public function testSetGetTotal()
    {
        $this->assertSame(0, $this->entity->getTotal());

        $this->entity->setTotal(10025);
        $this->assertSame(10025, $this->entity->getTotal());

        $this->entity->setTotal(10025);
        $this->assertEquals(new Money(10025, new Currency('INR')), $this->entity->getTotalMoney());

        $this->entity->setTotal(10025)->setCurrency('USD');
        $this->assertEquals(new Money(10025, new Currency('USD')), $this->entity->getTotalMoney());
    }

    public function testSetGetBaseTotal()
    {
        $this->assertSame(0, $this->entity->getBaseTotal());

        $this->entity->setBaseTotal(10025);
        $this->assertSame(10025, $this->entity->getBaseTotal());

        $this->entity->setBaseTotal(10025);
        $this->assertEquals(new Money(10025, new Currency('INR')), $this->entity->getBaseTotalMoney());

        $this->entity->setBaseTotal(10025)->setCurrency('USD');
        $this->assertEquals(new Money(10025, new Currency('INR')), $this->entity->getBaseTotalMoney());
    }

    public function testGetPaymentsApplied()
    {
        $this->assertEquals(new ArrayCollection(), $this->entity->getPaymentsApplied());
    }

    public function testGetStatus()
    {
        $this->assertEquals(OrderBill::STATUS_PAID, $this->entity->getStatus());

        $this->entity->setTotal(10025);
        $this->assertEquals(OrderBill::STATUS_OPEN, $this->entity->getStatus());

        $this->entity->setTotal(0);
        $orderPaymentApplied = $this->createConfiguredMock(
            OrderPaymentApplied::class,
            [
                'getAmount' => 20025,
                'getBill' => $this->entity
            ]
        );
        $orderPaymentsApplied = new ArrayCollection();
        $orderPaymentsApplied->add($orderPaymentApplied);
        $this->paymentsAppliedProperty->setValue($this->entity, $orderPaymentsApplied);
        $this->assertEquals(OrderBill::STATUS_OVERDUE, $this->entity->getStatus());
    }

    public function testGetPayments()
    {
        $this->assertEquals(new ArrayCollection(), $this->entity->getPayments());
    }

    public function testAddPay()
    {
        $bill = new OrderBill();
        $bill->setTotal(1000)
            ->setCurrency('INR');
        $bill2 = new OrderBill();
        $bill2->setTotal(1000)
            ->setCurrency('INR');
        $payment = new Payment();
        $payment->setTotal(800)
            ->setCurrency('INR');
        $payment2 = new Payment();
        $payment2->setTotal(1200)
            ->setCurrency('INR');
        //Control check unused amount
        $this->assertEquals(800, $payment->getUnusedAmount());
        $this->assertEquals(1200, $payment2->getUnusedAmount());
        // Use some payment for 1 bill
        $bill->addPayment($payment, 200);
        $this->assertEquals(600, $payment->getUnusedAmount());

        $bill2->addPayment($payment, 200);
        $this->assertEquals(400, $payment->getUnusedAmount());

        $bill->addPayment($payment2, 100);
        $this->assertEquals(1100, $payment2->getUnusedAmount());

        $bill->addPayment($payment, 200);
        $this->assertEquals(200, $payment->getUnusedAmount());

        $bill2->addPayment($payment2, 900);
        $this->assertEquals(200, $payment2->getUnusedAmount());

        $bill->addPayment($payment, 200);
        $this->assertEquals(0, $payment->getUnusedAmount());
        $bill2->addPayment($payment2, 100);
        $this->assertEquals(100, $payment2->getUnusedAmount());

        //$applied = $payment->getPaymentsApplied()->first();
        //$payment->getPaymentsApplied()->removeElement($applied);
        //$bill->getPaymentsApplied()->removeElement($applied);
        //$bill2->getPaymentsApplied()->removeElement($applied);
        //$this->assertEquals(800, $payment->getUnusedAmount());
        $this->expectException(\DomainException::class);
        $bill2->addPayment($payment2, 200);
    }

    /**
     * @dataProvider addPaymentData
     */
    public function testAddPayment($expected, $data, $exceptionMessage)
    {
        $this->entity->setTotal($data['entity']['total']);
        $this->entity->setCurrency($data['entity']['currency']);

        if ($exceptionMessage) {
            $this->expectExceptionMessage($exceptionMessage);
        }

        $payments = [];
        foreach ($data['payments'] as $payment) {
            $payments[$payment['index']] = (new Payment())
                ->setId($payment['id'])
                ->setCurrency($payment['currency'])
                ->setTotal($payment['total']);

            foreach ($payment['amountsForAdding'] as $amountForAdding) {
                $this->entity->addPayment($payments[$payment['index']], $amountForAdding);
            }
        }

        $this->assertEquals($expected['OrderPaymentAppliedCount'], $this->entity->getPaymentsApplied()->count());
        $this->assertEquals($expected['PaymentsCount'], $this->entity->getPayments()->count());
        $this->assertEquals($expected['status'], $this->entity->getStatus());
        $this->assertEquals($expected['paidAmount'], $this->entity->getPaidAmount());
    }

    public function addPaymentData()
    {
        return [
            'test1' => [
                'expected' => [
                    'OrderPaymentAppliedCount' => 1,
                    'PaymentsCount' => 1,
                    'status' => OrderBill::STATUS_PAID,
                    'paidAmount' => 10025,
                ],
                'data' => [
                    'payments' => [
                        [
                            'index' => 1,
                            'currency' => 'INR',
                            'total' => 10025,
                            'amountsForAdding' => [10025],
                            'id' => '20f246c1-625d-4cce-b0e4-6bbd4ea3dbe2',
                        ],
                    ],
                    'entity' => [
                        'total' => 10025,
                        'currency' => 'INR',
                    ]
                ],
                'exceptionMessage' => null,
            ],
            'test2' => [
                'expected' => [
                    'OrderPaymentAppliedCount' => 2,
                    'PaymentsCount' => 2,
                    'status' => OrderBill::STATUS_OVERDUE,
                    'paidAmount' => 20050,
                ],
                'data' => [
                    'payments' => [
                        [
                            'index' => 1,
                            'currency' => 'INR',
                            'total' => 10025,
                            'amountsForAdding' => [10025],
                            'id' => '20f246c1-625d-4cce-b0e4-6bbd4ea3dbe2',
                        ],
                        [
                            'index' => 2,
                            'currency' => 'INR',
                            'total' => 10025,
                            'amountsForAdding' => [10025],
                            'id' => '30f246c1-625d-4cce-b0e4-6bbd4ea3dbe3',
                        ],
                    ],
                    'entity' => [
                        'total' => 10025,
                        'currency' => 'INR',
                    ]
                ],
                'exceptionMessage' => null,
            ],
            'test3' => [
                'expected' => [
                    'OrderPaymentAppliedCount' => 2,
                    'PaymentsCount' => 2,
                    'status' => OrderBill::STATUS_PARTIALLY_PAID,
                    'paidAmount' => 10026,
                ],
                'data' => [
                    'payments' => [
                        [
                            'index' => 1,
                            'currency' => 'INR',
                            'total' => 10025,
                            'amountsForAdding' => [5025],
                            'id' => '20f246c1-625d-4cce-b0e4-6bbd4ea3dbe2',
                        ],
                        [
                            'index' => 2,
                            'currency' => 'INR',
                            'total' => 10025,
                            'amountsForAdding' => [5001],
                            'id' => '30f246c1-625d-4cce-b0e4-6bbd4ea3dbe3',
                        ],
                    ],
                    'entity' => [
                        'total' => 20050,
                        'currency' => 'INR',
                    ]
                ],
                'exceptionMessage' => null,
            ],
            'test4' => [
                'expected' => [
                    'OrderPaymentAppliedCount' => 1,
                    'PaymentsCount' => 1,
                    'status' => OrderBill::STATUS_PAID,
                    'paidAmount' => 10025,
                ],
                'data' => [
                    'payments' => [
                        [
                            'index' => 1,
                            'currency' => 'INR',
                            'total' => 10025,
                            'amountsForAdding' => [5025, 5000],
                            'id' => '20f246c1-625d-4cce-b0e4-6bbd4ea3dbe2',
                        ],
                    ],
                    'entity' => [
                        'total' => 10025,
                        'currency' => 'INR',
                    ]
                ],
                'exceptionMessage' => null,
            ],
            'test5' => [
                'expected' => [
                    'OrderPaymentAppliedCount' => 1,
                    'PaymentsCount' => 1,
                    'status' => OrderBill::STATUS_PAID,
                    'paidAmount' => 10025,
                ],
                'data' => [
                    'payments' => [
                        [
                            'index' => 1,
                            'currency' => 'INR',
                            'total' => 10025,
                            'amountsForAdding' => [5025, 5025],
                            'id' => '20f246c1-625d-4cce-b0e4-6bbd4ea3dbe2',
                        ],
                    ],
                    'entity' => [
                        'total' => 10025,
                        'currency' => 'INR',
                    ]
                ],
                'exceptionMessage' => 'Unused amount (5000) in payment (id: 20f246c1-625d-4cce-b0e4-6bbd4ea3dbe2, total: 10025) too small for apply 5025.'
            ],
            'test6' => [
                'expected' => [
                    'OrderPaymentAppliedCount' => 1,
                    'PaymentsCount' => 1,
                    'status' => OrderBill::STATUS_PAID,
                    'paidAmount' => 10025,
                ],
                'data' => [
                    'payments' => [
                        [
                            'index' => 1,
                            'currency' => 'INR',
                            'total' => 10025,
                            'amountsForAdding' => [5025, 5025],
                            'id' => '20f246c1-625d-4cce-b0e4-6bbd4ea3dbe2',
                        ],
                    ],
                    'entity' => [
                        'total' => 10025,
                        'currency' => 'USD',
                    ]
                ],
                'exceptionMessage' => 'Bill (id: 3839075a-3be1-4596-ae40-f65e8ae0eb29) & Payment (id: 20f246c1-625d-4cce-b0e4-6bbd4ea3dbe2) have different currency (USD != INR).'
            ],
        ];
    }
}
