<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Handler;

use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Model\Workflow\Order\Handler\ShipmentRequestSendBoxHandler;
use Boodmo\Sales\Service\InvoiceService;
use Boodmo\Sales\Service\OrderService;
use Boodmo\User\Service\SupplierService;
use Boodmo\Shipping\Service\ShippingService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class ShipmentRequestSendBoxHandlerTest extends TestCase
{
    /**
     * @var ShipmentRequestSendBoxHandler
     */
    private $handler;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityManager;

    /**
     * @var InvoiceService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $invoiceService;

    /**
     * @var ShippingService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingService;

    /**
     * @var OrderService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderService;

    /**
     * @var SupplierService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $supplierService;

    /**
     * @var \ReflectionMethod
     */
    private $isCorrectInvoiceSumMethod;

    /**
     * @var OrderBundle
     */
    private $orderBundle;

    /**
     * @var OrderPackage
     */
    private $orderPackage;

    public function setUp()
    {
        $this->entityManager = $this->createConfiguredMock(
            EntityManager::class,
            ['getConnection' => $this->createMock(Connection::class)]
        );
        $this->invoiceService = $this->createMock(InvoiceService::class);
        $this->shippingService = $this->createMock(ShippingService::class);
        $this->orderService = $this->createMock(OrderService::class);
        $this->supplierService = $this->createMock(supplierService::class);

        $this->handler = new ShipmentRequestSendBoxHandler(
            $this->entityManager,
            $this->invoiceService,
            $this->shippingService,
            $this->orderService,
            $this->supplierService
        );
        $reflector = new \ReflectionObject($this->handler);
        $this->isCorrectInvoiceSumMethod = $reflector->getMethod('isCorrectInvoiceSum');
        $this->isCorrectInvoiceSumMethod->setAccessible(true);

        $this->orderBundle = new OrderBundle();
        $this->orderPackage = (new OrderPackage())->setCurrency('INR');
        $this->orderBundle->addPackage($this->orderPackage);
    }

    /**
     * @dataProvider isCorrectInvoiceSumData
     */
    public function testIsCorrectInvoiceSum($expected, $data)
    {
        $bills = new ArrayCollection();
        foreach ($data['bills'] as $bill) {
            $bills->add(
                $this->createConfiguredMock(
                    OrderBill::class,
                    [
                        'getCurrency' => $bill['getCurrency'],
                        'getType' => $bill['getType'],
                        'getStatus' => $bill['getStatus'],
                    ]
                )
            );
        }
        $bundle = $this->createConfiguredMock(OrderBundle::class, ['getBills' => $bills]);

        $package = $this->createPartialMock(OrderPackage::class, ['getBundle', 'getCurrency']);
        $package->method('getBundle')->willReturn($bundle);
        $package->method('getCurrency')->willReturn($data['getCurrency']);

        $this->assertEquals($expected, $this->isCorrectInvoiceSumMethod->invoke($this->handler, $package));
    }

    public function isCorrectInvoiceSumData()
    {
        return [
            'test1' => [
                'expected' => true,
                'data' => [
                    'bills' => [],
                    'getCurrency' => 'INR'
                ]
            ],
            'test2' => [
                'expected' => true,
                'data' => [
                    'bills' => [
                        [
                            'getCurrency' => 'INR',
                            'getType' => OrderBill::TYPE_POSTPAID,
                            'getStatus' => OrderBill::STATUS_PAID
                        ],
                        [
                            'getCurrency' => 'INR',
                            'getType' => OrderBill::TYPE_POSTPAID,
                            'getStatus' => OrderBill::STATUS_OPEN
                        ],
                        [
                            'getCurrency' => 'INR',
                            'getType' => OrderBill::TYPE_POSTPAID,
                            'getStatus' => OrderBill::STATUS_PARTIALLY_PAID
                        ],
                        [
                            'getCurrency' => 'INR',
                            'getType' => OrderBill::TYPE_POSTPAID,
                            'getStatus' => OrderBill::STATUS_OVERDUE
                        ]
                    ],
                    'getCurrency' => 'INR'
                ]
            ],
            'test3' => [
                'expected' => true,
                'data' => [
                    'bills' => [
                        [
                            'getCurrency' => 'INR',
                            'getType' => OrderBill::TYPE_ON_DELIVERY,
                            'getStatus' => OrderBill::STATUS_PAID
                        ],
                        [
                            'getCurrency' => 'INR',
                            'getType' => OrderBill::TYPE_ON_DELIVERY,
                            'getStatus' => OrderBill::STATUS_OPEN
                        ],
                        [
                            'getCurrency' => 'INR',
                            'getType' => OrderBill::TYPE_ON_DELIVERY,
                            'getStatus' => OrderBill::STATUS_PARTIALLY_PAID
                        ],
                        [
                            'getCurrency' => 'INR',
                            'getType' => OrderBill::TYPE_ON_DELIVERY,
                            'getStatus' => OrderBill::STATUS_OVERDUE
                        ]
                    ],
                    'getCurrency' => 'INR'
                ]
            ],
            'test4' => [
                'expected' => true,
                'data' => [
                    'bills' => [
                        [
                            'getCurrency' => 'USD',
                            'getType' => OrderBill::TYPE_PREPAID,
                            'getStatus' => OrderBill::STATUS_PAID
                        ],
                        [
                            'getCurrency' => 'USD',
                            'getType' => OrderBill::TYPE_PREPAID,
                            'getStatus' => OrderBill::STATUS_OPEN
                        ],
                        [
                            'getCurrency' => 'USD',
                            'getType' => OrderBill::TYPE_PREPAID,
                            'getStatus' => OrderBill::STATUS_PARTIALLY_PAID
                        ],
                        [
                            'getCurrency' => 'USD',
                            'getType' => OrderBill::TYPE_PREPAID,
                            'getStatus' => OrderBill::STATUS_OVERDUE
                        ]
                    ],
                    'getCurrency' => 'INR'
                ]
            ],
            'test5' => [
                'expected' => false,
                'data' => [
                    'bills' => [
                        [
                            'getCurrency' => 'INR',
                            'getType' => OrderBill::TYPE_PREPAID,
                            'getStatus' => OrderBill::STATUS_PAID
                        ],
                        [
                            'getCurrency' => 'INR',
                            'getType' => OrderBill::TYPE_PREPAID,
                            'getStatus' => OrderBill::STATUS_OPEN
                        ],
                        [
                            'getCurrency' => 'INR',
                            'getType' => OrderBill::TYPE_PREPAID,
                            'getStatus' => OrderBill::STATUS_PARTIALLY_PAID
                        ],
                        [
                            'getCurrency' => 'INR',
                            'getType' => OrderBill::TYPE_PREPAID,
                            'getStatus' => OrderBill::STATUS_OVERDUE
                        ]
                    ],
                    'getCurrency' => 'INR'
                ]
            ],
            'test6' => [
                'expected' => true,
                'data' => [
                    'bills' => [
                        [
                            'getCurrency' => 'USD',
                            'getType' => OrderBill::TYPE_PREPAID,
                            'getStatus' => OrderBill::STATUS_PAID
                        ],
                        [
                            'getCurrency' => 'INR',
                            'getType' => OrderBill::TYPE_PREPAID,
                            'getStatus' => OrderBill::STATUS_OPEN
                        ],
                        [
                            'getCurrency' => 'INR',
                            'getType' => OrderBill::TYPE_PREPAID,
                            'getStatus' => OrderBill::STATUS_PARTIALLY_PAID
                        ],
                        [
                            'getCurrency' => 'USD',
                            'getType' => OrderBill::TYPE_PREPAID,
                            'getStatus' => OrderBill::STATUS_OVERDUE
                        ]
                    ],
                    'getCurrency' => 'USD'
                ]
            ],
            'test7' => [
                'expected' => false,
                'data' => [
                    'bills' => [
                        [
                            'getCurrency' => 'USD',
                            'getType' => OrderBill::TYPE_PREPAID,
                            'getStatus' => OrderBill::STATUS_OPEN
                        ],
                    ],
                    'getCurrency' => 'USD'
                ]
            ],
            'test8' => [
                'expected' => false,
                'data' => [
                    'bills' => [
                        [
                            'getCurrency' => 'USD',
                            'getType' => OrderBill::TYPE_PREPAID,
                            'getStatus' => OrderBill::STATUS_PARTIALLY_PAID
                        ],
                        [
                            'getCurrency' => 'USD',
                            'getType' => OrderBill::TYPE_PREPAID,
                            'getStatus' => OrderBill::STATUS_PARTIALLY_PAID
                        ],
                    ],
                    'getCurrency' => 'USD'
                ]
            ],
        ];
    }
}
