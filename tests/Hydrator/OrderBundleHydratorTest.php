<?php

namespace BoodmoApiSales\Test\Hydrator;

use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Hydrator\OrderBundleHydrator;
use Boodmo\Sales\Hydrator\OrderCreditPointAppliedHydrator;
use Boodmo\Sales\Hydrator\OrderPackageHydrator;
use Boodmo\Sales\Model\Workflow\Status\Status;
use Boodmo\User\Entity\UserProfile\Customer;
use Boodmo\User\Model\AddressBook;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\ArrayCollection;

class OrderBundleHydratorTest extends TestCase
{
    /**
     * @var MoneyService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $moneyService;

    /**
     * @var OrderPackageHydrator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderPackageHydrator;

    /**
     * @var orderCreditPointAppliedHydrator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderCreditPointAppliedHydrator;

    /**
     * @var OrderBundleHydrator
     */
    private $orderBundleHydrator;

    public function setUp()
    {
        $this->moneyService = $this->createPartialMock(MoneyService::class, ['getMoney']);
        $this->orderPackageHydrator = $this->createMock(OrderPackageHydrator::class);
        $this->orderCreditPointAppliedHydrator = $this->createMock(OrderCreditPointAppliedHydrator::class);

        $this->orderBundleHydrator = new OrderBundleHydrator(
            $this->moneyService,
            $this->orderPackageHydrator,
            $this->orderCreditPointAppliedHydrator
        );
    }

    /**
     * @dataProvider extractData
     */
    public function testExtract($expected, $preInit)
    {
        $orderBundle = new OrderBundle();
        if ($preInit && is_callable($preInit)) {
            $preInit($orderBundle);
        }
        $this->moneyService->method('getMoney')->will($this->returnCallback(function ($price, $currency) {
            return new Money($price * 100, new Currency($currency));
        }));

        $this->assertEquals($expected, $this->orderBundleHydrator->extract($orderBundle));
    }

    public function extractData()
    {
        return [
            'test1' => [
                'expected' => [
                    'items_count' => 1,
                    'base_grand_total' => new Money(215050, new Currency('INR')),
                    'base_delivery_total' => new Money(10025, new Currency('INR')),
                    'grand_total' => [
                        'INR' => new Money(215050, new Currency('INR'))
                    ],
                    'delivery_total' => [
                        'INR' => new Money(10025, new Currency('INR'))
                    ],
                    'id' => 1,
                    'number' => '0101/300001',
                    'created_at' => new \DateTime('2017-01-01'),
                    'packages' => [],
                    'customerAddress' => [],
                    'payment_method' => 'paypal.com',
                    'customer_status' => 'Empty',
                    'bills' => [],
                    'refunds' => [],
                    'status' => [Status::TYPE_GENERAL => ''],
                    'customer_email' => '',
                    'client_ip' => '',
                    'affiliate' => 'web',
                    'customer' => [
                        'id' => null,
                        'phone' => '',
                        'pin' => '',
                        'first_name' => '',
                        'last_name' => '',
                        'full_name' => ' ',
                        'address_book' => new AddressBook(),
                        'cohort_source' => null,
                        'cohort_medium' => null,
                        'address' => [],
                        'cars' => [],
                        'docs' => [],
                        'crm_link' => null,
                        'user' => null,
                        'workshop' => false,
                        'representative' => '',

                    ],
                    'paid_money'        => [],
                    'packages_money'    => ['INR' => new Money(215050, new Currency('INR'))],
                    'refunds_money'     => [],
                    'notes'             => [],
                ],
                'preInit' => function (OrderBundle $orderBundle) {
                    $package = (new OrderPackage())->setCurrency('INR');
                    $item = (new OrderItem())
                        ->setPrice(205025)
                        ->setDeliveryPrice(10025)
                        ->setBasePrice(205025)
                        ->setBaseDeliveryPrice(10025);
                    $orderBundle->setPackages(new ArrayCollection())
                        ->setId(1)
                        ->setCreatedAt(new \DateTime('2017-01-01'))
                        ->setPaymentMethod('paypal.com');
                    $orderBundle->addPackage($package->addItem($item));
                    $orderBundle->setCustomerProfile(new Customer());
                }
            ]
        ];
    }
}
