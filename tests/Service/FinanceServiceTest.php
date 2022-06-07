<?php

namespace Boodmo\SalesTest\Service;

use Boodmo\Email\Service\EmailManager;
use Boodmo\Sales\Entity\CreditPoint;
use Boodmo\Sales\Repository\OrderCreditPointAppliedRepository;
use Boodmo\Sales\Repository\OrderPackageRepository;
use Boodmo\Sales\Service\FinanceService;
use Boodmo\Sales\Service\PaymentService;
use Boodmo\Shipping\Service\ShippingService;
use Boodmo\User\Service\AddressService;
use OpsWay\ZohoBooks\Api;
use PHPUnit\Framework\TestCase;

class FinanceServiceTest extends TestCase
{
    /**
     * @var FinanceService
     */
    protected $service;

    /**
     * @var ShippingService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingService;

    /**
     * @var AddressService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $addressService;

    /**
     * @var PaymentService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentService;

    /**
     * @var OrderPackageRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderPackageRepository;

    /**
     * @var EmailManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $emailManager;

    /**
     * @var Api|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $zohoBooks;

    /**
     * @var Api|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $zohoBooks2;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $config2;

    /**
     * @var OrderCreditPointAppliedRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderCreditPointAppliedRepository;


    /**
     * @var \ReflectionMethod
     */
    protected $getAccountIdByCreditPointMethod;

    public function setup()
    {
        $this->shippingService = $this->createMock(ShippingService::class);
        $this->addressService = $this->createMock(AddressService::class);
        $this->paymentService = $this->createMock(PaymentService::class);
        $this->orderPackageRepository = $this->createMock(OrderPackageRepository::class);
        $this->emailManager = $this->createMock(EmailManager::class);
        $this->zohoBooks = $this->createMock(Api::class);
        $this->zohoBooks2 = $this->createMock(Api::class);
        $this->config = [
            'price_increased_expense'   => 'price_increased_expense_test',
            'customer_claim_accepted'   => 'customer_claim_accepted_test',
            'gross_sales'               => 'gross_sales_test',
        ];
        $this->config2 = [];
        $this->orderCreditPointAppliedRepository = $this->createMock(OrderCreditPointAppliedRepository::class);

        $this->service = new FinanceService(
            $this->shippingService,
            $this->addressService,
            $this->paymentService,
            $this->orderPackageRepository,
            $this->emailManager,
            $this->zohoBooks,
            $this->zohoBooks2,
            $this->config,
            $this->config2,
            $this->orderCreditPointAppliedRepository
        );
        $reflector = new \ReflectionObject($this->service);
        $this->getAccountIdByCreditPointMethod = $reflector->getMethod('getAccountIdByCreditPoint');
        $this->getAccountIdByCreditPointMethod->setAccessible(true);
    }

    public function testGetAccountIdByCreditPoint()
    {
        $this->assertEquals(
            $this->config['price_increased_expense'],
            $this->getAccountIdByCreditPointMethod->invoke(
                $this->service,
                (new CreditPoint())->setType(CreditPoint::TYPE_PRICE_INCREASED_BY_SUPPLIER)
            )
        );

        $this->assertEquals(
            $this->config['customer_claim_accepted'],
            $this->getAccountIdByCreditPointMethod->invoke(
                $this->service,
                (new CreditPoint())->setType(CreditPoint::TYPE_CLAIM_ACCEPTED)
            )
        );

        $this->assertEquals(
            $this->config['gross_sales'],
            $this->getAccountIdByCreditPointMethod->invoke(
                $this->service,
                (new CreditPoint())->setType(CreditPoint::TYPE_TRANSFER_OF_UNAPPLIED_PAYMENT)
            )
        );

        $this->expectExceptionMessage('Undefined account for CreditPoint type: other_type');
        $this->getAccountIdByCreditPointMethod->invoke($this->service, (new CreditPoint())->setType('other_type'));
    }
}
