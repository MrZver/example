<?php

namespace Boodmo\SalesTest\Model\Payment\Provider;

use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Entity\Payment;
use Boodmo\Sales\Model\Payment\Provider\PayPalProvider;
use Boodmo\Sales\Service\PaymentService;
use PayPal\Transport\PayPalRestCall;
use PayPal\Api\Invoice;
use PayPal\Api\Metadata;
use Zend\Http\Request;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

/**
 * Class PayPalProviderTest
 * @package Boodmo\SalesTest\Model\Payment\Provider
 * @coversDefaultClass \Boodmo\Sales\Model\Payment\Provider\PayPalProvider
 */
class PayPalProviderTest extends TestCase
{
    /**
     * @var PayPalProvider
     */
    private $paymentProvider;

    /**
     * @var PaymentService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentService;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var \ReflectionMethod
     */
    private $calculateItemsTotalMethod;

    /**
     * @var \ReflectionMethod
     */
    private $getOrderItemsMethod;

    public function setUp()
    {
        $this->paymentProvider = new PayPalProvider();
        $reflector = new \ReflectionObject($this->paymentProvider);
        $this->calculateItemsTotalMethod = $reflector->getMethod('calculateItemsTotal');
        $this->calculateItemsTotalMethod->setAccessible(true);
//        $this->getOrderItemsMethod = $reflector->getMethod('getOrderItems');
//        $this->getOrderItemsMethod->setAccessible(true);

        $this->paymentService = $this->createPartialMock(PaymentService::class, ['markAsPaid', 'getPayment']);
        $this->paymentService->method('markAsPaid')->willReturn(true);

        $this->request = $this->createPartialMock(Request::class, ['getContent']);
    }

    /**
     * @covers ::capture
     * @dataProvider captureData
     */
    public function testCapture($data)
    {
        $this->request
            ->method('getContent')
            ->willReturn(json_encode($data['getContent']));
        if (isset($data['getContent']['resource'])) {
            $this->paymentService
                ->expects($this->once())
                ->method('markAsPaid')
                ->with(
                    $data['getContent']['resource']['invoice']['reference'],
                    $data['getContent']['resource']['invoice']['id'],
                    $data['getContent']['resource']['invoice']['paidAmount']['paypal']['value'] * 100
                );
        }
        $this->paymentProvider->capture($this->paymentService, $this->request);
        $this->assertTrue(true);
    }

    /**
     * @dataProvider calculateItemsTotalData
     */
    public function testCalculateItemsTotal($expected, $data, $kf)
    {
        /* @var OrderBundle $orderBundle*/
        $items = new ArrayCollection();
        foreach ($data as $item) {
            $items->add(
                (new OrderItem())->setName($item['name'])->setQty($item['qty'])->setPrice($item['price'])
                ->setDeliveryPrice($item['delivery_price'])
            );
        }

        $packages = new ArrayCollection();
        $packages->add((new OrderPackage())->setItems($items));

        $orderBundle = $this->createConfiguredMock(OrderBundle::class, ['getPackagesWithCurrency' => $packages]);
        $this->paymentProvider->setKf($kf);
        $this->assertEquals($expected, $this->calculateItemsTotalMethod->invoke($this->paymentProvider, $orderBundle));
    }

    /*public function testGetOrderItems()
    {
        $package1 = (new OrderPackage())->setCurrency('INR');
        $package2 = (new OrderPackage())->setCurrency('USD');
        $package3 = (new OrderPackage())->setCurrency('USD');

        $orderItem1 = (new OrderItem())->setName('item1');
        $orderItem2 = (new OrderItem())->setName('item2');
        $orderItem3 = (new OrderItem())->setName('item3');
        $orderItem4 = (new OrderItem())->setName('item4');
        $orderItem5 = (new OrderItem())->setName('item5');

        $collectionPackage1 = new ArrayCollection();
        $collectionPackage1->add($orderItem1);
        $collectionPackage1->add($orderItem2);
        $package1->setItems($collectionPackage1);

        $collectionPackage2 = new ArrayCollection();
        $collectionPackage2->add($orderItem3);
        $collectionPackage2->add($orderItem4);
        $package2->setItems($collectionPackage2);

        $collectionPackage3 = new ArrayCollection();
        $collectionPackage3->add($orderItem5);
        $package3->setItems($collectionPackage3);

        $collectionPackages = new ArrayCollection();
        $collectionPackages->add($package1);
        $collectionPackages->add($package2);
        $collectionPackages->add($package3);

        $bundle = new OrderBundle();
        $bundle->setPackages($collectionPackages);

        $this->assertEquals(
            [$orderItem3, $orderItem4, $orderItem5],
            $this->getOrderItemsMethod->invoke($this->paymentProvider, $bundle)
        );
    }*/

    /**
     * @covers ::setApiKey
     * @covers ::getApiKey
     */
    public function testGetSetApiKey()
    {
        $this->assertEquals('', $this->paymentProvider->getApiKey());

        $this->paymentProvider->setApiKey('test');
        $this->assertEquals('test', $this->paymentProvider->getApiKey());
    }

    /**
     * @covers ::setSecretKey
     * @covers ::getSecretKey
     */
    public function testGetSetSecretKey()
    {
        $this->assertEquals('', $this->paymentProvider->getSecretKey());

        $this->paymentProvider->setSecretKey('test');
        $this->assertEquals('test', $this->paymentProvider->getSecretKey());
    }

    /**
     * @covers ::setMerchantEmail
     * @covers ::getMerchantEmail
     */
    public function testGetSetMerchantEmail()
    {
        $this->assertEquals('', $this->paymentProvider->getMerchantEmail());

        $this->paymentProvider->setMerchantEmail('test');
        $this->assertEquals('test', $this->paymentProvider->getMerchantEmail());
    }

    /**
     * @covers ::setMerchantFirstName
     * @covers ::getMerchantFirstName
     */
    public function testGetSetMerchantFirstName()
    {
        $this->assertEquals('', $this->paymentProvider->getMerchantFirstName());

        $this->paymentProvider->setMerchantFirstName('test');
        $this->assertEquals('test', $this->paymentProvider->getMerchantFirstName());
    }

    /**
     * @covers ::setMerchantLastName
     * @covers ::getMerchantLastName
     */
    public function testGetSetMerchantLastName()
    {
        $this->assertEquals('', $this->paymentProvider->getMerchantLastName());

        $this->paymentProvider->setMerchantLastName('test');
        $this->assertEquals('test', $this->paymentProvider->getMerchantLastName());
    }

    /**
     * @covers ::setCompany
     * @covers ::getCompany
     */
    public function testGetSetCompany()
    {
        $this->assertEquals('', $this->paymentProvider->getCompany());

        $this->paymentProvider->setCompany('test');
        $this->assertEquals('test', $this->paymentProvider->getCompany());
    }

    /**
     * @covers ::setMerchantAddress
     * @covers ::getMerchantAddress
     */
    public function testGetSetMerchantAddress()
    {
        $this->assertEquals('', $this->paymentProvider->getMerchantAddress());

        $this->paymentProvider->setMerchantAddress('test');
        $this->assertEquals('test', $this->paymentProvider->getMerchantAddress());
    }

    /**
     * @covers ::setMerchantCity
     * @covers ::getMerchantCity
     */
    public function testGetSetMerchantCity()
    {
        $this->assertEquals('', $this->paymentProvider->getMerchantCity());

        $this->paymentProvider->setMerchantCity('test');
        $this->assertEquals('test', $this->paymentProvider->getMerchantCity());
    }

    /**
     * @covers ::setMerchantState
     * @covers ::getMerchantState
     */
    public function testGetSetMerchantState()
    {
        $this->assertEquals('', $this->paymentProvider->getMerchantState());

        $this->paymentProvider->setMerchantState('test');
        $this->assertEquals('test', $this->paymentProvider->getMerchantState());
    }

    /**
     * @covers ::setMerchantPostal
     * @covers ::getMerchantPostal
     */
    public function testGetSetMerchantPostal()
    {
        $this->assertEquals('', $this->paymentProvider->getMerchantPostal());

        $this->paymentProvider->setMerchantPostal('test');
        $this->assertEquals('test', $this->paymentProvider->getMerchantPostal());
    }

    /**
     * @covers ::setMerchantCountryCode
     * @covers ::getMerchantCountryCode
     */
    public function testGetSetMerchantCountryCode()
    {
        $this->assertEquals('', $this->paymentProvider->getMerchantCountryCode());

        $this->paymentProvider->setMerchantCountryCode('test');
        $this->assertEquals('test', $this->paymentProvider->getMerchantCountryCode());
    }

    /**
     * @covers ::setKf
     * @covers ::getKf
     */
    public function testGetSetKf()
    {
        $this->assertEquals(1, $this->paymentProvider->getKf());

        $this->paymentProvider->setKf(2);
        $this->assertEquals(2, $this->paymentProvider->getKf());

        $this->paymentProvider->setKf(2.5);
        $this->assertEquals(2.5, $this->paymentProvider->getKf());

        $this->paymentProvider->setKf('3.0');
        $this->assertEquals(3, $this->paymentProvider->getKf());
    }

    /**
     * @covers ::setRestCall
     * @covers ::getRestCall
     */
    public function testGetSetRestCall()
    {
        $this->paymentProvider->setRestCall('key', 'value');
        $this->assertEquals('value', $this->paymentProvider->getRestCall('key'));
    }

    /**
     * @covers ::getInvoiceOld
     * @dataProvider getInvoiceData
     *
     * Пользователь оплатил первый инвойс и была сгенерирована доплата
     * Payments (is_open=false, is_open= true)
     */
    /*public function testGetInvoiceWithSurchargeCase1($data)
    {
        $provider = $this->paymentProvider;
        $provider->setLiveMode(false);

        $restCall = $this->createMock(PayPalRestCall::class);
        $restCall->method('execute')->willReturn($data['search']);
        $provider->setRestCall('search', $restCall);

        $restCall = $this->createMock(PayPalRestCall::class);
        $restCall->method('execute')->willReturn($data['response']);
        $provider->setRestCall('create', $restCall);

        $restCall = $this->createMock(PayPalRestCall::class);
        $restCall->method('execute')->willReturn($data['send']);
        $provider->setRestCall('send', $restCall);

        $restCall = $this->createMock(PayPalRestCall::class);
        $restCall->method('execute')->willReturn($data['response']);
        $provider->setRestCall('get', $restCall);

        $date = new \DateTime();
        $bundle = new OrderBundle();
        $bundle->setCreatedAt($date);

        $payment1 = new Payment();
        $payment1->setPaymentMethod($data['method']);
        $payment1->setBundle($bundle);
        $payment1->setCreatedAt($date);

        $payment2 = new Payment();
        $payment2->setPaymentMethod($data['method']);
        $payment2->setBundle($bundle);
        $payment2->setCreatedAt($date);

        $bundle->addPayment($payment1);
        $bundle->addPayment($payment2);

        $invoice = $provider->getInvoiceOld([], [], $payment2);
        $this->assertEquals($data['result_id'], $invoice->getId());
    }*/

    /**
     * @covers ::getInvoiceOld
     * @dataProvider getInvoiceData
     *
     * Пользователь оплатил первый инвойс и была сгенерирована доплата1 и чуть позже доплата2
     * Сумма доплаты = доплата1 + доплата2
     */
    /*public function testGetInvoiceWithSurchargeCase2($data)
    {
        $provider = $this->paymentProvider;
        $provider->setLiveMode(false);

        $restCall = $this->createMock(PayPalRestCall::class);
        $restCall->method('execute')->willReturn($data['search']);
        $provider->setRestCall('search', $restCall);

        $restCall = $this->createMock(PayPalRestCall::class);
        $restCall->method('execute')->willReturn($data['response']);
        $provider->setRestCall('create', $restCall);

        $restCall = $this->createMock(PayPalRestCall::class);
        $restCall->method('execute')->willReturn($data['send']);
        $provider->setRestCall('send', $restCall);

        $restCall = $this->createMock(PayPalRestCall::class);
        $restCall->method('execute')->willReturn($data['response']);
        $provider->setRestCall('get', $restCall);

        $date = new \DateTime();
        $bundle = new OrderBundle();
        $bundle->setCreatedAt($date);

        $item = new OrderItem();
        $oldPrice = 100;
        $item->setPrice($oldPrice * 100);
        $package = new OrderPackage();
        $package->addItem($item);
        $bundle->addPackage($package);

        $payment1 = new Payment();
        $payment1->setPaymentMethod($data['method']);
        $payment1->setBundle($bundle);
        $payment1->setCreatedAt($date);

        $payment2 = new Payment();
        $payment2->setPaymentMethod($data['method']);
        $payment2->setBundle($bundle);
        $payment2->setCreatedAt($date);

        $surcharge1 = 10;
        $payment2->setTotal($surcharge1 * 100);

        $payment3 = new Payment();
        $payment3->setPaymentMethod($data['method']);
        $payment3->setBundle($bundle);
        $payment3->setCreatedAt($date);

        $surcharge2 = 20;
        $payment3->setTotal($surcharge2 * 100);

        $bundle->addPayment($payment1);
        $bundle->addPayment($payment2);
        $bundle->addPayment($payment3);

        $invoice = $provider->getInvoiceOld([], $package->getItems()->toArray(), $payment2);
        $newSurcharge1 = $provider->getInvoice($payment2)->getItems()[0]->getUnitPrice()->getValue();

        $invoice = $provider->getInvoiceOld([], $package->getItems()->toArray(), $payment3);
        $newSurcharge2 = $provider->getInvoice($payment3)->getItems()[0]->getUnitPrice()->getValue();

        $this->assertEquals($surcharge1 + $surcharge2, $newSurcharge1 + $newSurcharge2);
    }*/

    /**
     * @covers ::getInvoiceOld
     * @dataProvider getInvoiceData
     *
     * Пользователь оплатил первый инвойс и была сгенерирована доплата1. Пользователь оплачивает доплату1. Чуть позже создана доплата2
     * 2 Payments (is_open=false, is_open=false, is_open= true)
     */
    /*public function testGetInvoiceWithSurchargeCase3($data)
    {
        $provider = $this->paymentProvider;
        $provider->setLiveMode(false);

        $restCall = $this->createMock(PayPalRestCall::class);
        $restCall->method('execute')->willReturn($data['search']);
        $provider->setRestCall('search', $restCall);

        $restCall = $this->createMock(PayPalRestCall::class);
        $restCall->method('execute')->willReturn($data['response']);
        $provider->setRestCall('create', $restCall);

        $restCall = $this->createMock(PayPalRestCall::class);
        $restCall->method('execute')->willReturn($data['send']);
        $provider->setRestCall('send', $restCall);

        $restCall = $this->createMock(PayPalRestCall::class);
        $restCall->method('execute')->willReturn($data['response']);
        $provider->setRestCall('get', $restCall);

        $date = new \DateTime();
        $bundle = new OrderBundle();
        $bundle->setCreatedAt($date);

        $payment1 = new Payment();
        $payment1->setPaymentMethod($data['method']);
        $payment1->setBundle($bundle);
        $payment1->setCreatedAt($date);

        $payment2 = new Payment();
        $payment2->setPaymentMethod($data['method']);
        $payment2->setBundle($bundle);
        $payment2->setCreatedAt($date);

        $payment3 = new Payment();
        $payment3->setPaymentMethod($data['method']);
        $payment3->setBundle($bundle);
        $payment3->setCreatedAt($date);

        $bundle->addPayment($payment1);
        $bundle->addPayment($payment2);
        $bundle->addPayment($payment3);

        $invoice = $provider->getInvoiceOld([], [], $payment2);
        $this->assertEquals($data['result_id'], $invoice->getId());

        $invoice = $provider->getInvoiceOld([], [], $payment3);
        $this->assertEquals($data['result_id'], $invoice->getId());
    }*/

    /**
     * @covers ::getInvoiceOld
     * @dataProvider getInvoiceData
     *
     * Пользователь НЕ оплатил первый инвойс и увеличилась цена
     * Инвойс в PayPal низменяет суммы и пользователь получает заказ с новой суммой
     */
    /*public function testGetInvoiceWithSurchargeCase4($data)
    {
        $provider = $this->paymentProvider;
        $provider->setLiveMode(false);

        $restCall = $this->createMock(PayPalRestCall::class);
        $restCall->method('execute')->willReturn($data['search']);
        $provider->setRestCall('search', $restCall);

        $restCall = $this->createMock(PayPalRestCall::class);
        $restCall->method('execute')->willReturn($data['response']);
        $provider->setRestCall('create', $restCall);

        $restCall = $this->createMock(PayPalRestCall::class);
        $restCall->method('execute')->willReturn($data['send']);
        $provider->setRestCall('send', $restCall);

        $restCall = $this->createMock(PayPalRestCall::class);
        $restCall->method('execute')->willReturn($data['response']);
        $provider->setRestCall('get', $restCall);

        $date = new \DateTime();
        $bundle = new OrderBundle();
        $bundle->setCreatedAt($date);

        $item = new OrderItem();
        $oldPrice = 100;
        $item->setPrice($oldPrice * 100);
        $package = new OrderPackage();
        $package->addItem($item);
        $bundle->addPackage($package);

        $payment1 = new Payment();
        $payment1->setPaymentMethod($data['method']);
        $payment1->setBundle($bundle);
        $payment1->setCreatedAt($date);

        $newPrice = 150;
        $item->setPrice($newPrice * 100);

        $payment2 = new Payment();
        $payment2->setPaymentMethod($data['method']);
        $payment2->setBundle($bundle);
        $payment2->setCreatedAt($date);

        $bundle->addPayment($payment1);
        $bundle->addPayment($payment2);

        $invoice = $provider->getInvoiceOld([], $package->getItems()->toArray(), $payment2);
        $this->assertEquals($newPrice, $provider->getInvoice($payment2)->getItems()[0]->getUnitPrice()->getValue());
    }*/

    /**
     * @covers ::authorize
     * @dataProvider getAuthorizeData
     */
    public function testAuthorize($data)
    {
        $provider = $this->paymentProvider;
        $response =
            '{
                "id": "INV2-RF6D-L66T-D7H2-CRU7",
                "number": "0002",
                "status": "DRAFT",
                "metadata": {
                    "created_date": "2014-03-24 12:11:52 PDT",
                    "updated_date": "2014-03-24 12:11:52 PDT",
                    "payer_view_url": "https://www.paypal.com/invoice/payerView/details/INV2-RF6D-L66T-D7H2-CRU7"
                }
            }';

        $restCall = $this->createMock(PayPalRestCall::class);
        $restCall->method('execute')->willReturn('{
                        "invoices": []
                    }');
        $provider->setRestCall('search', $restCall);

        $restCall = $this->createMock(PayPalRestCall::class);
        $restCall->method('execute')->willReturn($response);
        $provider->setRestCall('create', $restCall);

        $restCall = $this->createMock(PayPalRestCall::class);
        $restCall->method('execute')->willReturn('202 Accepted');
        $provider->setRestCall('send', $restCall);


//        $mockMetadata = $this->createMock(Metadata::class);
//        $mockMetadata->expects($this->once())->method('getPayerViewUrl')->willReturn($data['external_link']);
//
        $mockInvoice = $this->createMock(Invoice::class);
//        $mockInvoice->expects($this->once())->method('getMetadata')->willReturn($mockMetadata);

        $provider->setInvoice($mockInvoice);

        $date = new \DateTime('2017-08-29T06:00:00Z');
        $bundle = new OrderBundle();
        $bundle->setCustomerEmail($data['email']);
        $bundle->setCustomerAddress($data['address']);
        $bundle->setCreatedAt($date);
        $bundle->setId('999');
        $orderBill = (new OrderBill())
            ->setBundle($bundle)
            ->setCreatedAt($date)
            ->setUpdatedAt($date)
            ->setTotal($data['amount'])
            ->setId($data['bill_id']);

        $result = $this->paymentProvider->authorize($this->paymentService, $orderBill);
        unset($data['address']);
        $this->assertEquals($data, $result);
    }

    /**
     * @covers ::fillingInvoice
     * @dataProvider getAuthorizeData
     */
    public function testFillingInvoice($data)
    {
        $provider = $this->paymentProvider;

        $date = new \DateTime('2017-08-29T06:00:00Z');
        $bundle = new OrderBundle();
        $bundle->setCustomerEmail($data['email']);
        $bundle->setCustomerAddress($data['address']);
        $bundle->setCreatedAt($date);
        $bundle->setId('999');
        $orderBill = (new OrderBill())
            ->setBundle($bundle)
            ->setCreatedAt($date)
            ->setUpdatedAt($date)
            ->setTotal($data['amount'])
            ->setId($data['bill_id']);

        $invoice = $provider->fillingInvoice(new Invoice(), $orderBill);

        $this->assertSame('NET_90', $invoice->getPaymentTerm()->getTermType());
        $this->assertSame($data['bill_id'], $invoice->getReference());
        $this->assertSame($data['number'], $invoice->getNumber());
        $this->assertSame('https://boodmo.com/img/logo.png', $invoice->getLogoUrl());
        $this->assertSame("91", $invoice->getMerchantInfo()->getPhone()->getCountryCode());
        $this->assertSame("5032141716", $invoice->getMerchantInfo()->getPhone()->getNationalNumber());
        $this->assertSame("IN", $invoice->getShippingInfo()->getAddress()->getCountryCode());
    }

    /**
     * @covers ::getInvoiceNumber
     * @dataProvider getAuthorizeData
     */
    public function testGetInvoiceNumber($data)
    {
        $provider = $this->paymentProvider;

        $date = new \DateTime('2017-08-29T06:00:00Z');
        $bundle = new OrderBundle();
        $bundle->setCustomerEmail($data['email']);
        $bundle->setCustomerAddress($data['address']);
        $bundle->setCreatedAt($date);
        $bundle->setId('999');
        $orderBill = (new OrderBill())
            ->setBundle($bundle)
            ->setCreatedAt($date)
            ->setUpdatedAt($date)
            ->setTotal($data['amount'])
            ->setId($data['bill_id']);

        $number = $provider->getInvoiceNumber($orderBill);
        $this->assertEquals("999/08290600", $number);
    }

    public function captureData()
    {
        return [
            'test1' => [
                'data' => [
                    'getContent' => []
                ],
            ],
            'test2' => [
                'data' => [
                    'getContent' => [
                        'resource' => [
                            'invoice' => [
                                'id' => '1',
                                'reference' => 1,
                                'paidAmount' => ['paypal' => ['value' => 10.25]]
                            ]
                        ]
                    ]
                ],
            ]
        ];
    }

    public function getInvoiceData()
    {
        return [
            'test1' => [
                'data' => [
                    'method' => 'paypal',
                    'search' => '{
                        "invoices": []
                    }',
                    'response' => '{
                        "id": "INV2-RF6D-L66T-D7H2-CRU7",
                        "number": "0002",
                        "status": "DRAFT",
                        "metadata": {
                            "created_date": "2014-03-24 12:11:52 PDT"
                        }
                    }',
                    'send' => '202 Accepted',
                    'result_id' => "INV2-RF6D-L66T-D7H2-CRU7",
                ],
            ],
        ];
    }

    public function getAuthorizeData()
    {
        return [
            'test1' => [
                'data' => [
                    'address' => [
                        'first_name' => '',
                        'last_name' => '',
                        'city' => '',
                        'state' => '',
                        'pin' => '',
                        'phone' => '',
                        'address' => '',
                    ],
                    'number' => '999/08290600',
                    'email' => 'test@email.com',
                    'amount' => 15000,
                    'bill_id' => '520596d2-e606-4968-bdf1-94b62b8f76b4',
                    'external_link' => "https://www.paypal.com/invoice/payerView/details/INV2-RF6D-L66T-D7H2-CRU7",
                ],
            ],
        ];
    }

    public function calculateItemsTotalData()
    {
        return [
            'test1' => [
                'expected' => [
                    110,
                    100,
                    [
                        ['qty' => 1, 'price' => 100, 'name' => 'item1'],
                    ]
                ],
                'data' => [
                    ['price' => 10000, 'delivery_price' => 1000, 'qty' => 1, 'name' => 'item1'],
                ],
                'kf' => 1
            ],
            'test2' => [
                'expected' => [
                    97.72,
                    46.83,
                    [
                        ['qty' => 1, 'price' => 15.61, 'name' => 'item1'],
                        ['qty' => 2, 'price' => 15.61, 'name' => 'item2'],
                    ]
                ],
                'data' => [
                    ['price' => 3121, 'delivery_price' => 195, 'qty' => 1, 'name' => 'item1'],
                    ['price' => 3121, 'delivery_price' => 107, 'qty' => 2, 'name' => 'item2'],
                ],
                'kf' => 2
            ],
            'test3' => [
                'expected' => [
                    97.72,
                    42.57,
                    [
                        ['qty' => 1, 'price' => 14.19, 'name' => 'item1'],
                        ['qty' => 2, 'price' => 14.19, 'name' => 'item2'],
                    ]
                ],
                'data' => [
                    ['price' => 3121, 'delivery_price' => 195, 'qty' => 1, 'name' => 'item1'],
                    ['price' => 3121, 'delivery_price' => 107, 'qty' => 2, 'name' => 'item2'],
                ],
                'kf' => 2.2
            ],
            'test4' => [
                'expected' => [
                    97.80,
                    42.59,
                    [
                        ['qty' => 1, 'price' => 14.19, 'name' => 'item1'],
                        ['qty' => 2, 'price' => 14.20, 'name' => 'item2'],
                    ]
                ],
                'data' => [
                    ['price' => 3121, 'delivery_price' => 195, 'qty' => 1, 'name' => 'item1'],
                    ['price' => 3125, 'delivery_price' => 107, 'qty' => 2, 'name' => 'item2'],
                ],
                'kf' => 2.2
            ]
        ];
    }
}
