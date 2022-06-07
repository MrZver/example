<?php

namespace Boodmo\SalesTest\Model\Payment\Provider;

use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Model\Payment\Provider\HdfcProvider;
use Boodmo\Sales\Service\PaymentService;
use Zend\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * Class HdfcProviderTest
 * @package Boodmo\SalesTest\Model\Payment\Provider
 * @coversDefaultClass \Boodmo\Sales\Model\Payment\Provider\HdfcProvider
 */
class HdfcProviderTest extends TestCase
{
    /**
     * @var HdfcProvider
     */
    private $paymentProvider;

    /**
     * @var PaymentService
     */
    private $paymentService;

    public function setUp()
    {
        $this->paymentProvider = new HdfcProvider();
        $this->paymentService = $this->getMockBuilder(PaymentService::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @covers ::authorize
     */
    public function testAuthorize()
    {
        $orderBill = $this->createConfiguredMock(OrderBill::class, ['getStatus' => OrderBill::STATUS_OPEN]);
        $this->assertEquals([], $this->paymentProvider->authorize($this->paymentService, $orderBill));
    }

    /**
     * @covers ::capture
     */
    public function testCapture()
    {
        $request = $this->getMockBuilder(Request::class)->getMock();
        $this->paymentProvider->capture($this->paymentService, $request);
        $this->assertTrue(true);
    }
}
