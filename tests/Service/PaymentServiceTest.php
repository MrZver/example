<?php

namespace Boodmo\SalesTest\Service;

use Boodmo\Email\Service\EmailManager;
use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Model\Payment\Provider\RazorPayProvider;
use Boodmo\Sales\Repository\CreditPointRepository;
use Boodmo\Sales\Repository\OrderBillRepository;
use Boodmo\Sales\Repository\OrderCreditPointAppliedRepository;
use Boodmo\Sales\Repository\OrderPaymentAppliedRepository;
use Boodmo\Sales\Repository\PaymentRepository;
use Boodmo\Sales\Service\FinanceService;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Sales\Service\PaymentService;
use PHPUnit\Framework\TestCase;
use Prooph\ServiceBus\CommandBus;
use Zend\Log\Logger;

class PaymentServiceTest extends TestCase
{
    /**
     * @var PaymentService
     */
    protected $service;

    /**
     * @var
     */
    protected $providers;

    /**
     * @var
     */
    protected $config;

    /**
     * @var PaymentRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentRepository;

    /**
     * @var FinanceService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $financeService;

    /**
     * @var OrderService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderService;

    /**
     * @var EmailManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $emailManager;

    /**
     * @var CommandBus|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $commandBus;

    /**
     * @var OrderBillRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderBillRepository;

    /**
     * @var CreditPointRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $creditPointRepository;

    /**
     * @var OrderPaymentAppliedRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderPaymentAppliedRepository;

    /**
     * @var OrderCreditPointAppliedRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderCreditPointAppliedRepository;

    /**
     * @var Logger|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    public function setUp()
    {
        $this->providers = [
            RazorPayProvider::class,
        ];
        $this->config = [];
        $this->paymentRepository = $this->createMock(PaymentRepository::class);
        $this->financeService = $this->createMock(FinanceService::class);
        $this->orderService = $this->createMock(OrderService::class);
        $this->emailManager = $this->createMock(EmailManager::class);
        $this->commandBus = $this->createMock(CommandBus::class);
        $this->orderBillRepository = $this->createPartialMock(OrderBillRepository::class, ['find']);
        $this->creditPointRepository = $this->createMock(CreditPointRepository::class);
        $this->orderPaymentAppliedRepository = $this->createMock(OrderPaymentAppliedRepository::class);
        $this->orderCreditPointAppliedRepository = $this->createMock(OrderCreditPointAppliedRepository::class);
        $this->logger = $this->createMock(Logger::class);

        $this->service = new PaymentService(
            $this->providers,
            $this->config,
            $this->paymentRepository,
            $this->financeService,
            $this->orderService,
            $this->emailManager,
            $this->commandBus,
            $this->orderBillRepository,
            $this->creditPointRepository,
            $this->orderPaymentAppliedRepository,
            $this->orderCreditPointAppliedRepository,
            $this->logger
        );
    }

    /**
     * @dataProvider getPaymentAuthorizationDataData
     */
    public function testGetPaymentAuthorizationData($expected, $data)
    {
        $this->orderBillRepository->method('find')->willReturn($data['bill']);
        if (!empty($expected['exception'])) {
            $this->expectExceptionMessage($expected['exception']);
        }
        $result = $this->service->getPaymentAuthorizationData('701d750d-1982-4306-aeab-f4a3d0d591a1');
        if (!empty($expected['data'])) {
            $this->assertEquals($expected['data']['data'], $result['data']);
        }
    }

    public function getPaymentAuthorizationDataData()
    {
        return [
            'test1' => [
                'expected' => [
                    'exception' => 'Wrong request for payment gateway.',
                    'data' => [],
                ],
                'data' => [
                    'bill' => null
                ],
            ],
            'test2' => [
                'expected' => [
                    'exception' => 'We\'re apologise, but your Order has been canceled and can\'t be paid.',
                    'data' => [],
                ],
                'data' => [
                    'bill' => (new OrderBill())
                        ->setPaymentMethod('razorpay')
                        ->setBundle((new OrderBundle())->setStatus(['G' => 'CANCELLED']))
                ],
            ],
            'test3' => [
                'expected' => [
                    'exception' => 'Sorry, this payment has been already received.',
                    'data' => [],
                ],
                'data' => [
                    'bill' => (new OrderBill())
                        ->setPaymentMethod('razorpay')
                        ->setTotal(0)
                        ->setBundle(new OrderBundle())
                ],
            ],
            'test4' => [
                'expected' => [
                    'exception' => '',
                    'data' => [
                        'data' => [
                            'number'    => '1201/500002',
                            'orderID'   => 2,
                            'email'     => 'test@test.com',
                            'paymentID' => '701d750d-1982-4306-aeab-f4a3d0d591a1',
                            'amount'    => 10025,
                            'name'      => 'test_first_name test_last_name',
                            'phone'     => '1234567890',
                            'apiKey'    => '',
                        ]
                    ],
                ],
                'data' => [
                    'bill' => (new OrderBill())
                        ->setPaymentMethod('razorpay')
                        ->setId('701d750d-1982-4306-aeab-f4a3d0d591a1')
                        ->setTotal(10025)
                        ->setBundle(
                            (new OrderBundle())->setCreatedAt(new \DateTime('2018-01-12'))
                                ->setId(2)
                                ->setCustomerEmail('test@test.com')
                                ->setCustomerAddress(
                                    [
                                        'first_name' => 'test_first_name',
                                        'last_name' => 'test_last_name',
                                        'phone' => '1234567890'
                                    ]
                                )
                        )
                ],
            ],
        ];
    }
}
