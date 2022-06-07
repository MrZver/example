<?php

namespace BoodmoApiSales\Test\Hydrator;

use Boodmo\Sales\Entity\Payment;
use Boodmo\Sales\Hydrator\PaymentHydrator;
use Doctrine\Common\Collections\ArrayCollection;
use Money\Currency;
use Money\Money;
use Ramsey\Uuid\Uuid;
use PHPUnit\Framework\TestCase;
use Zend\Stdlib\ArrayObject;

class PaymentHydratorTest extends TestCase
{
    /**
     * @var PaymentHydrator
     */
    private $hydrator;

    public function setUp()
    {
        $this->hydrator = new PaymentHydrator();
    }

    /**
     * @dataProvider extractData
     */
    public function testExtract($expected, $preInit = null)
    {
        $id = (string)Uuid::uuid4();
        $expected['id'] = $id;
        $payment = (new Payment())->setId($id);
        if ($preInit and is_callable($preInit)) {
            $preInit($payment);
        }

        $this->assertEquals($expected, $this->hydrator->extract($payment));
    }

    public function extractData()
    {
        return [
            'test1' => [
                'expected' => [
                    'transaction_id' => null,
                    'total' => 0,
                    'total_money' => new Money(0, new Currency('INR')),
                    'zoho_books_id' => '',
                    'currency_rate' => 0.0,
                    'history_trans' => [],
                    'payment_method' => null,
                    'currency' => 'INR',
                    'base_total' => 0,
                    'base_total_money' => new Money(0, new Currency('INR')),
                    'created_at' => null,
                    'updated_at' => null,
                    'customer_profile' => null,
                    'payments_applied' => new ArrayCollection(),
                    'used_amount' => 0,
                    'unused_amount' => 0,
                    'bills' => new ArrayCollection(),
                ],
                'preInit' => null
            ],
            'test2' => [
                'expected' => [
                    'transaction_id' => null,
                    'total' => 2517,
                    'total_money' => new Money(2517, new Currency('USD')),
                    'zoho_books_id' => '',
                    'currency_rate' => 65.0,
                    'history_trans' => [],
                    'payment_method' => null,
                    'currency' => 'USD',
                    'base_total' => 163605,
                    'base_total_money' => new Money(163605, new Currency('INR')),
                    'created_at' => null,
                    'updated_at' => null,
                    'customer_profile' => null,
                    'payments_applied' => new ArrayCollection(),
                    'used_amount' => 0,
                    'unused_amount' => 2517,
                    'bills' => new ArrayCollection(),
                ],
                'preInit' => function (Payment $payment) {
                    $payment->setTotal(2517)->setCurrency('USD')->setBaseTotal(163605);
                }
            ]
        ];
    }
}
