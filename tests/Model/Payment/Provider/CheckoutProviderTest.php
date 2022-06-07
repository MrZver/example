<?php

namespace Boodmo\SalesTest\Model\Payment\Provider;

use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Model\Payment\Provider\CheckoutProvider;
use Boodmo\Sales\Service\PaymentService;
use com\checkout\ApiServices\Tokens\RequestModels\PaymentTokenCreate;
use Zend\Http\Header\GenericHeader;
use Zend\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * Class CheckoutProviderTest
 * @package Boodmo\SalesTest\Model\Payment\Provider
 * @coversDefaultClass \Boodmo\Sales\Model\Payment\Provider\CheckoutProvider
 */
class CheckoutProviderTest extends TestCase
{
    /**
     * @var CheckoutProvider
     */
    private $paymentProvider;

    /**
     * @var paymentService
     */
    private $paymentService;

    /**
     * @var Request
     */
    private $request;

    public function setUp()
    {
        $this->paymentProvider = new CheckoutProvider();
        $this->paymentService = $this->createMock(PaymentService::class);
        $this->request = $this->createPartialMock(Request::class, ['getContent', 'getHeader']);
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
    public function testCaptureWebHookKey()
    {
        $this->request->method('getContent')->willReturn(json_encode([
            'eventType' => 'charge.captured',
            'message' => ['metadata' => ['payment_id' => 1], 'id' => 1],
        ]));
        $this->paymentProvider->capture($this->paymentService, $this->request);
        $this->assertTrue(true);
    }

    /**
     * @covers ::capture
     */
    public function testCaptureMarkAsPaid()
    {
        $this->paymentProvider->setHookKey('testHookKey');

        $genericHeader = $this->createConfiguredMock(GenericHeader::class, ['getFieldValue' => 'testHookKey']);

        $this->request->method('getContent')->willReturn(json_encode([
            'eventType' => 'charge.captured',
            'message' => [
                'metadata' => ['payment_id' => 1, 'bill_id' => '9e68b55c-d002-4047-bb0c-ac45a19cb8ec'],
                'id' => 1,
                'value' => 100
            ],
        ]));
        $this->request->method('getHeader')->with('Authorization')->willReturn($genericHeader);

        $this->paymentProvider->capture($this->paymentService, $this->request);
        $this->assertTrue(true);
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
     * @covers ::setPublicKey
     * @covers ::getPublicKey
     */
    public function testGetSetPublicKey()
    {
        $this->assertEquals('', $this->paymentProvider->getPublicKey());

        $this->paymentProvider->setPublicKey('test');
        $this->assertEquals('test', $this->paymentProvider->getPublicKey());
    }

    /**
     * @covers ::setHookKey
     * @covers ::getHookKey
     */
    public function testGetSetHookKey()
    {
        $this->assertEquals('', $this->paymentProvider->getHookKey());

        $this->paymentProvider->setHookKey('test');
        $this->assertEquals('test', $this->paymentProvider->getHookKey());
    }

    /**
     * @covers ::getTokenPayload
     */
    public function testGetTokenPayload()
    {
        $orderBundle = $this->createConfiguredMock(
            OrderBundle::class,
            [
                'getClientIp' => '127.0.0.1',
                'getNumber' => '1',
                'getCustomerEmail' => 'test@test.test',
                'getId' => '1',
            ]
        );
        $orderBill = $this->createConfiguredMock(
            OrderBill::class,
            [
                'getTotal' => 100,
                'getId' => 1,
            ]
        );


        $this->assertInstanceOf(
            PaymentTokenCreate::class,
            $this->paymentProvider->getTokenPayload($orderBundle, $orderBill)
        );
    }
}
