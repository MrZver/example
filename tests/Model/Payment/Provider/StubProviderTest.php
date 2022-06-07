<?php

namespace Boodmo\SalesTest\Model\Payment\Provider;

use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Model\Payment\PaymentProviderInterface;
use Boodmo\Sales\Service\PaymentService;
use PHPUnit\Framework\TestCase;
use Zend\Log\LoggerInterface;

/**
 * Class StubProviderTest
 * @package Boodmo\SalesTest\Model\Payment\Provider
 * @coversDefaultClass \Boodmo\Sales\Model\Payment\Provider\AbstractPaymentProvider
 */
class StubProviderTest extends TestCase
{
    /**
     * @var StubProvider
     */
    private $paymentProvider;

    /**
     * @var
     */
    private $data = [
            'name'                  => 'stub',
            'prepaid'               => false,
            'label'                 => '',
            'sort'                  => 0,
            'active'                => true,
            'live_mode'             => 1,
            'base_currency'         => 'INR',
            'zoho_payment_account'  => '',
            'disabled'              => false,
            'config'                => null,
            'logger'                => null
        ];

    public function setUp()
    {
        $this->paymentProvider = new StubProvider();
    }

    /**
     * @covers ::getConfig
     * @covers ::setConfig
     */
    public function testGetSetConfig()
    {
        $this->assertEquals($this->data['config'], $this->paymentProvider->getConfig());

        $this->paymentProvider->setConfig(['test_1' => 'test']);
        $this->assertEquals(['test_1' => 'test'], $this->paymentProvider->getConfig());
    }

    /**
     * @covers ::getName
     */
    public function testGetName()
    {
        $this->assertEquals($this->data['name'], $this->paymentProvider->getName());
    }

    /**
     * @covers ::getCode
     */
    public function testGetCode()
    {
        $this->assertEquals(StubProvider::CODE, StubProvider::getCode());
    }

    /**
     * @covers ::getViewTemplate
     */
    public function testGetViewTemplate()
    {
        $this->assertEquals(StubProvider::VIEW_TEMPLATE, $this->paymentProvider->getViewTemplate());
    }

    /**
     * @covers ::isPrepaid
     */
    public function testIsPrepaid()
    {
        $this->assertEquals($this->data['prepaid'], $this->paymentProvider->isPrepaid());
    }

    /**
     * @covers ::getOptions
     */
    public function testGetOptions()
    {
        $this->assertEquals($this->data, $this->paymentProvider->getOptions());

        $this->paymentProvider->setActive(false);
        $this->assertEquals(
            array_merge($this->data, ['active' => false]),
            $this->paymentProvider->getOptions()
        );
    }

    /**
     * @covers ::authorize
     */
    public function testAuthorize()
    {
        $paymentService = $this->createMock(PaymentService::class);
        $orderBill = $this->createConfiguredMock(OrderBill::class, ['getStatus' => OrderBill::STATUS_OPEN]);
        $this->assertEquals([], $this->paymentProvider->authorize($paymentService, $orderBill));
    }

    /**
     * @covers ::authorize
     */
    public function testAuthorizeException()
    {
        $paymentService = $this->createMock(PaymentService::class);
        $orderBill = $this->createConfiguredMock(
            OrderBill::class,
            [
                'getStatus' => OrderBill::STATUS_PAID,
                'getId' => '701d750d-1982-4306-aeab-f4a3d0d591a1',
                'getBundle' => new OrderBundle()
            ]
        );
        $this->expectExceptionMessage('Payment is paid (bill id: 701d750d-1982-4306-aeab-f4a3d0d591a1)');
        $this->paymentProvider->authorize($paymentService, $orderBill);
    }

    /**
     * @covers ::authorize
     */
    public function testAuthorizeExceptionCanceled()
    {
        $paymentService = $this->createMock(PaymentService::class);
        $orderBill = $this->createConfiguredMock(
            OrderBill::class,
            [
                'getStatus' => OrderBill::STATUS_PAID,
                'getId' => '701d750d-1982-4306-aeab-f4a3d0d591a1',
                'getBundle' => (new OrderBundle())->setStatus(['G' => 'CANCELLED'])->setId(1)
            ]
        );
        $this->expectExceptionMessage('Order is canceled (bill id: 701d750d-1982-4306-aeab-f4a3d0d591a1, order id: 1)');
        $this->paymentProvider->authorize($paymentService, $orderBill);
    }

    /**
     * @covers ::getZohoPaymentAccount
     * @covers ::setZohoPaymentAccount
     */
    public function testGetSetZohoPaymentAccount()
    {
        $this->assertEquals(
            $this->data['zoho_payment_account'],
            $this->paymentProvider->getZohoPaymentAccount()
        );

        $this->paymentProvider->setZohoPaymentAccount('test');
        $this->assertEquals('test', $this->paymentProvider->getZohoPaymentAccount());

        $this->paymentProvider->setConfig(['account_stub' => 'test']);
        $this->assertEquals('test', $this->paymentProvider->getZohoPaymentAccount());
    }

    /**
     * @covers ::getLabel
     * @covers ::setLabel
     */
    public function testGetSetLabel()
    {
        $this->assertEquals($this->data['label'], $this->paymentProvider->getLabel());

        $this->paymentProvider->setLabel('test');
        $this->assertEquals('test', $this->paymentProvider->getLabel());
    }

    /**
     * @covers ::getSort
     * @covers ::setSort
     */
    public function testGetSetSort()
    {
        $this->assertEquals($this->data['sort'], $this->paymentProvider->getSort());

        $this->paymentProvider->setSort(1);
        $this->assertEquals(1, $this->paymentProvider->getSort());
    }

    /**
     * @covers ::isActive
     * @covers ::getActive
     * @covers ::setActive
     */
    public function testIsGetSetActive()
    {
        $this->assertEquals($this->data['active'], $this->paymentProvider->getActive());
        $this->assertEquals($this->data['active'], $this->paymentProvider->isActive());

        $this->paymentProvider->setActive(false);
        $this->assertFalse($this->paymentProvider->getActive());
        $this->assertFalse($this->paymentProvider->isActive());
    }

    /**
     * @covers ::getLiveMode
     * @covers ::setLiveMode
     */
    public function testGetSetLiveMode()
    {
        $this->assertEquals($this->data['live_mode'], $this->paymentProvider->getLiveMode());

        $this->paymentProvider->setLiveMode(false);
        $this->assertFalse($this->paymentProvider->getLiveMode());
    }

    /**
     * @covers ::getBaseCurrency
     * @covers ::setBaseCurrency
     */
    public function testGetSetBaseCurrency()
    {
        $this->assertEquals($this->data['base_currency'], $this->paymentProvider->getBaseCurrency());

        $this->paymentProvider->setBaseCurrency('USD');
        $this->assertEquals('USD', $this->paymentProvider->getBaseCurrency());
    }

    /**
     * @covers ::isDisabled
     * @covers ::setDisabled
     */
    public function testIsSetDisabled()
    {
        $this->assertEquals($this->data['disabled'], $this->paymentProvider->isDisabled());

        $this->paymentProvider->setDisabled(true);
        $this->assertTrue($this->paymentProvider->isDisabled());
    }

    public function testSetLogger()
    {
        $this->assertInstanceOf(
            PaymentProviderInterface::class,
            $this->paymentProvider->setLogger($this->createMock(LoggerInterface::class))
        );
    }
}
