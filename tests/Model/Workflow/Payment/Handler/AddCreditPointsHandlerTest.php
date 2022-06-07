<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Handler;

use Boodmo\Currency\Service\CurrencyService;
use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Entity\CreditPoint;
use Boodmo\Sales\Model\Workflow\Payment\Handler\AddCreditPointsHandler;
use Boodmo\Sales\Service\FinanceService;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Sales\Service\PaymentService;
use Boodmo\User\Entity\UserProfile\Customer;
use Boodmo\User\Repository\CustomerRepository;
use Boodmo\User\Service\CustomerService;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class AddCreditPointsHandlerTest extends TestCase
{
    /**
     * @var AddCreditPointsHandler
     */
    protected $handler;

    /**
     * @var MoneyService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $moneyService;

    /**
     * @var FinanceService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $financeService;

    /**
     * @var OrderService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderService;

    /**
     * @var CustomerService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerService;

    /**
     * @var PaymentService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentService;

    /**
     * @var customerRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerRepository;

    /**
     * @var \ReflectionMethod
     */
    protected $getCustomerMethod;

    /**
     * @var \ReflectionMethod
     */
    protected $getCreditPointMethod;

    public function setUp()
    {
        $this->customerRepository = $this->createPartialMock(CustomerRepository::class, ['find']);

        $this->moneyService = $this->getMockBuilder(MoneyService::class)
            ->setConstructorArgs([$this->createConfiguredMock(CurrencyService::class, ['getCurrencyRate' => 65.00])])
            ->setMethods(['getMoney'])
            ->getMock();
        $this->financeService = $this->createMock(FinanceService::class);
        $this->orderService = $this->createMock(OrderService::class);
        $this->customerService = $this->createConfiguredMock(
            CustomerService::class,
            ['getRepository' => $this->customerRepository]
        );
        $this->paymentService = $this->createMock(PaymentService::class);

        $this->handler = new AddCreditPointsHandler(
            $this->moneyService,
            $this->financeService,
            $this->orderService,
            $this->customerService,
            $this->paymentService
        );

        $reflector = new \ReflectionObject($this->handler);
        $this->getCustomerMethod = $reflector->getMethod('getCustomer');
        $this->getCustomerMethod->setAccessible(true);
        $this->getCreditPointMethod = $reflector->getMethod('getCreditPoint');
        $this->getCreditPointMethod->setAccessible(true);
    }

    public function testGetCustomer()
    {
        $this->customerRepository->method('find')->willReturnCallback(function ($id) {
            $map = [
                1 => (new Customer())->setId($id)
            ];
            return $map[$id] ?? null;
        });

        $this->assertEquals(1, $this->getCustomerMethod->invoke($this->handler, 1)->getId());

        $this->expectExceptionMessage('Customer not found (id: 2)');
        $this->getCustomerMethod->invoke($this->handler, 2);
    }

    /**
     * @dataProvider getCreditPointData
     */
    public function testGetCreditPoint($data)
    {
        /* @var CreditPoint $creditPoint*/

        $this->moneyService->method('getMoney')->willReturn(
            new Money($data['total'], new Currency($data['currency']))
        );
        $creditPoint = $this->getCreditPointMethod->invoke(
            $this->handler,
            $data['customer'],
            $data['total'],
            $data['currency'],
            $data['type'],
            $data['zohoBooksId']
        );
        $this->assertEquals($data['customer'], $creditPoint->getCustomerProfile());
        $this->assertEquals($data['total'], $creditPoint->getTotal());
        $this->assertEquals($data['currency'], $creditPoint->getCurrency());
        $this->assertEquals($data['type'], $creditPoint->getType());
        $this->assertEquals($data['zohoBooksId'], $creditPoint->getZohoBooksId());
        $this->assertEquals($data['base_total'], $creditPoint->getBaseTotal());
    }

    public function getCreditPointData()
    {
        return [
            'test1' => [
                'data' => [
                    'customer' => (new Customer())->setId(1),
                    'total' => 12500,
                    'base_total' => 12500,
                    'currency' => 'INR',
                    'type' => CreditPoint::TYPE_PRICE_INCREASED_BY_SUPPLIER,
                    'zohoBooksId' => '',
                ]
            ],
            'test2' => [
                'data' => [
                    'customer' => (new Customer())->setId(2),
                    'total' => 15561,
                    'base_total' => 1011500,
                    'currency' => 'USD',
                    'type' => CreditPoint::TYPE_CLAIM_ACCEPTED,
                    'zohoBooksId' => '123',
                ]
            ]
        ];
    }
}
