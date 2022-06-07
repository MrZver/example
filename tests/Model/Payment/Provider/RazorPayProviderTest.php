<?php

namespace Boodmo\SalesTest\Model\Payment\Provider;

use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Model\Payment\Provider\RazorPayProvider;
use Boodmo\Sales\Service\PaymentService;
use Zend\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * Class RazorPayProviderTest
 * @package Boodmo\SalesTest\Model\Payment\Provider
 * @coversDefaultClass \Boodmo\Sales\Model\Payment\Provider\RazorPayProvider
 */
class RazorPayProviderTest extends TestCase
{
    /**
     * @var RazorPayProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentProvider;

    /**
     * @var PaymentService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentService;

    /**
     * @var OrderBundle|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderBundle;

    /**
     * @var OrderBill|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderBill;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    public function setUp()
    {
        $this->paymentProvider = $this->getMockBuilder(RazorPayProvider::class)
            ->setMethods(['getPaymentFromApi'])
            ->getMock();

        $this->paymentService = $this->createPartialMock(PaymentService::class, ['loadBill']);

        $this->orderBundle = $this->getMockBuilder(OrderBundle::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerAddress', 'getNumber', 'getCustomerEmail', 'getId'])
            ->getMock();

        $this->orderBill = $this->getMockBuilder(OrderBill::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBundle', 'getStatus', 'getId', 'getPaymentDue'])
            ->getMock();
        $this->orderBill->method('getBundle')->willReturn($this->orderBundle);

        $this->request = $this->getMockBuilder(Request::class)->getMock();
    }

    /**
     * @covers ::authorize
     * @dataProvider authorizeData
     */
    public function testAuthorize($data, $expected)
    {
        $this->orderBundle->method('getCustomerAddress')->willReturn($data['getCustomerAddress']);
        $this->orderBundle->method('getNumber')->willReturn($data['getNumber']);
        $this->orderBundle->method('getCustomerEmail')->willReturn($data['getCustomerEmail']);
        $this->orderBundle->method('getId')->willReturn($data['getId']);
        $this->orderBill->method('getId')->willReturn($data['getId']);
        $this->orderBill->method('getPaymentDue')->willReturn($data['getPaymentDue']);
        $this->orderBill->method('getStatus')->willReturn(OrderBill::STATUS_OPEN);

        $this->assertEquals($expected, $this->paymentProvider->authorize($this->paymentService, $this->orderBill));
    }

    /**
     * @covers ::capture
     */
    public function testCapture()
    {
        $this->request->method('getContent')->willReturn(json_encode([]));
        $this->paymentProvider->capture($this->paymentService, $this->request);
        $this->assertTrue(true);
    }

    /**
     * @covers ::capture
     */
    public function testCaptureException()
    {
        $this->paymentProvider->method('getPaymentFromApi')->willReturn(null);
        $this->paymentService->method('loadBill')->willReturn(
            (new OrderBill())->setId('92b50b86-dea8-40ed-8883-dfc0f0ee6081')->setTotal(100)
        );
        $this->request->method('getContent')->willReturn(json_encode([
            'payload' => [
                'payment' => [
                    'entity' => [
                        'notes' => [
                            'order_id' => 1,
                            'bill_id' => '92b50b86-dea8-40ed-8883-dfc0f0ee6081',
                        ],
                        'id' => 1
                    ]
                ]
            ],
        ]));
        $this->expectExceptionMessage(
            "Payment gateway didn't find your payment (payment id: 1, bill id: 92b50b86-dea8-40ed-8883-dfc0f0ee6081)."
        );
        $this->paymentProvider->capture($this->paymentService, $this->request);
        $this->assertTrue(true);
    }

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

    public function authorizeData()
    {
        return [
            'test1' => [
                'data' => [
                    'getCustomerAddress' => [
                        'first_name' => '',
                        'last_name' => '',
                        'phone' => ''
                    ],
                    'getNumber' => '',
                    'getCustomerEmail' => '',
                    'getId' => 1,
                    'getPaymentDue' => 1,
                ],
                'expected' => [
                    'number' => '',
                    'orderID' => 1,
                    'email' => '',
                    'paymentID' => '1',
                    'amount' => 1,
                    'name' => ' ',
                    'phone' => '',
                    'apiKey' => '',
                ],
            ],
            'test2' => [
                'data' => [
                    'getCustomerAddress' => [
                        'first_name' => 'test',
                        'last_name' => 'test',
                        'phone' => '123456789'
                    ],
                    'getNumber' => 'testNumber',
                    'getCustomerEmail' => 'test@test.test',
                    'getId' => 2,
                    'getPaymentDue' => 200,
                ],
                'expected' => [
                    'number' => 'testNumber',
                    'orderID' => 2,
                    'email' => 'test@test.test',
                    'paymentID' => '2',
                    'amount' => 200,
                    'name' => 'test test',
                    'phone' => '123456789',
                    'apiKey' => '',
                ],
            ]
        ];
    }
}
