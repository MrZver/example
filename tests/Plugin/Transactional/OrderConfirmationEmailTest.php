<?php

namespace Boodmo\SalesTest\Plugin\Transactional;

use Boodmo\Core\Service\SiteSettingService;
use Boodmo\Currency\Service\CurrencyService;
use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Model\Payment\Provider\CashProvider;
use Boodmo\Sales\Model\Payment\Provider\RazorPayProvider;
use Boodmo\Sales\Model\Workflow\Status\Status;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Plugin\Transactional\OrderConfirmationEmail;
use Boodmo\Sales\Service\PaymentService;
use Boodmo\User\Entity\UserProfile\Supplier;
use Doctrine\Common\Collections\ArrayCollection;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class OrderConfirmationEmailTest extends TestCase
{
    /**
     * @var OrderConfirmationEmail
     */
    protected $plugin;

    /**
     * @var SiteSettingService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $siteSettingService;

    /**
     * @var PaymentService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentService;

    /**
     * @var MoneyService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $moneyService;

    public function setUp()
    {
        $this->siteSettingService = $this->createPartialMock(SiteSettingService::class, ['getSettingByPath']);
        $this->siteSettingService->method('getSettingByPath')->with('general/sales_form_email')
            ->willReturn('admin@test.com');
        $this->paymentService = $this->createPartialMock(PaymentService::class, ['getProviderByCode']);
        $this->paymentService->method('getProviderByCode')->willReturnCallback(function ($code) {
            $map = [
                'razorpay' => (new RazorPayProvider())->setLabel('razorpay'),
                'cash' => (new CashProvider())->setLabel('cash')
            ];
            return $map[$code];
        });
        $this->moneyService = $this->getMockBuilder(MoneyService::class)
            ->setConstructorArgs([$this->createConfiguredMock(CurrencyService::class, ['getCurrencyRate' => 65.00])])
            ->setMethods(['getMoney'])
            ->getMock();
        $this->moneyService->method('getMoney')->willReturnCallback(function ($price, $currency) {
            return new Money($price * 100, new Currency($currency));
        });

        $this->plugin = new OrderConfirmationEmail(
            $this->siteSettingService,
            $this->paymentService,
            $this->moneyService
        );
    }

    public function testSetGetOrder()
    {
        $order = (new OrderBundle())->setId(1);
        $this->assertEquals($order, $this->plugin->setOrder($order)->getOrder());
    }

    public function testGetVars()
    {
        $packages = new ArrayCollection();
        $package1 = (new OrderPackage())
            ->setSupplierProfile((new Supplier())->setName('supplier1'))
            ->setNumber(1)
            ->setCurrency('USD')
            ->addItem(
                (new OrderItem())
                    ->setName('item_name1')
                    ->setNumber('item_number1')
                    ->setBrand('item_brand1')
                    ->setQty(1)
                    ->setPrice(10025)
                    ->setDeliveryPrice(5075)
                    ->setBasePrice(651625)
                    ->setBaseDeliveryPrice(329875)
            );
        $package2 = (new OrderPackage())
            ->setSupplierProfile((new Supplier())->setName('supplier2'))
            ->setNumber(2)
            ->setCurrency('INR')
            ->addItem(
                (new OrderItem())
                    ->setName('item_name2')
                    ->setNumber('item_number2')
                    ->setBrand('item_brand2')
                    ->setQty(2)
                    ->setPrice(11025)
                    ->setDeliveryPrice(5275)
            )->addItem(
                (new OrderItem())
                    ->setName('item_name3')
                    ->setNumber('item_number3')
                    ->setBrand('item_brand3')
                    ->setQty(1)
                    ->setPrice(10025)
                    ->setDeliveryPrice(5075)
                    ->setStatus([Status::TYPE_GENERAL => StatusEnum::CANCELLED])
            );
        $packages->add($package1);
        $packages->add($package2);

        $order = (new OrderBundle())
            ->setCreatedAt(new \DateTime('2017-11-01'))
            ->setCustomerEmail('test@test.test')
            ->setCustomerAddress([
                'first_name' => 'test_first_name',
                'last_name' => 'test_last_name',
                'pin' => '123123',
                'phone' => '1234567890',
                'address' => 'Some street',
                'city' => 'Some city',
                'state' => 'Some state',
            ])
            ->setId(1)
            ->setPaymentMethod('razorpay,cash')
            ->setPackages($packages);

        $this->plugin->setOrder($order);
        $this->assertEquals(
            [
                'order' => [
                    'originalId' => 1,
                    'id' => '0111/300001',
                    'email' => 'test@test.test',
                    'total' => '₹ 9,815.00 (₹ 326.00 + $ 151.00)',
                    'name' => 'test_first_name test_last_name',
                    'pin' => '123123',
                    'telephone' => '1234567890',
                    'address' => 'Some street',
                    'city' => 'Some city',
                    'state' => 'Some state',
                    'method' => "razorpay,\ncash",
                    'packages' => [
                        [
                            'id' => 1,
                            'name' => 'supplier1',
                            'sold' => 'supplier1',
                            'delivery' => '$ 50.75',
                            'package_total' => '$ 100.25',
                            'items' => [
                                [
                                    'id' => 1,
                                    'name' => 'item_name1',
                                    'number' => 'item_number1',
                                    'brand' => 'item_brand1',
                                    'qty' => 1,
                                    'price' => '$ 100.25',
                                    'delivery-item' => '$ 50.75',
                                    'subtotal' => '$ 100.25'
                                ]
                            ]
                        ],
                        [
                            'id' => 2,
                            'name' => 'supplier2',
                            'sold' => 'supplier2',
                            'delivery' => '₹ 106.00',
                            'package_total' => '₹ 221.00',
                            'items' => [
                                [
                                    'id' => 1,
                                    'name' => 'item_name2',
                                    'number' => 'item_number2',
                                    'brand' => 'item_brand2',
                                    'qty' => 2,
                                    'price' => '₹ 111.00',
                                    'delivery-item' => '₹ 53.00',
                                    'subtotal' => '₹ 221.00'
                                ]
                            ]
                        ],
                    ],
                ]
            ],
            $this->plugin->getVars()
        );
    }
}
