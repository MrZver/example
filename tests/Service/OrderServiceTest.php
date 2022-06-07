<?php

namespace Boodmo\SalesTest\Service;

use Boodmo\Core\Repository\SiteSettingRepository;
use Boodmo\Core\Service\SiteSettingService;
use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Entity\CancelReason;
use Boodmo\Sales\Entity\OrderBid;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Entity\OrderRma;
use Boodmo\Sales\Model\Workflow\Status\Status;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Model\Workflow\StatusWorkflow;
use Boodmo\Sales\Repository\CancelReasonRepository;
use Boodmo\Sales\Repository\OrderBundleRepository;
use Boodmo\Sales\Repository\OrderItemRepository;
use Boodmo\Sales\Repository\OrderPackageRepository;
use Boodmo\Sales\Repository\OrderRmaRepository;
use Boodmo\Sales\Service\NotificationService;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Shipping\Entity\ShippingBox;
use Boodmo\Shipping\Service\ShippingService;
use Boodmo\User\Entity\User;
use Boodmo\User\Entity\UserProfile\Customer;
use Boodmo\User\Entity\UserProfile\Supplier;
use Boodmo\User\Service\AddressService;
use Boodmo\User\Service\SupplierService;
use Boodmo\User\Service\UserService;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prooph\ServiceBus\CommandBus;

class OrderServiceTest extends TestCase
{
    /**
     * @var OrderService
     */
    protected $service;

    /**
     * @var OrderBundleRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderBundleRepository;

    /**
     * @var OrderPackageRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderPackageRepository;

    /**
     * @var SupplierService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $supplierService;

    /**
     * @var UserService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $userService;

    /**
     * @var OrderItemRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderItemRepository;

    /**
     * @var SiteSettingRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $siteSettingRepository;

    /**
     * @var ShippingService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingService;

    /**
     * @var AddressService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $addressService;

    /**
     * @var CancelReasonRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cancelReasonRepository;

    /**
     * @var NotificationService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $notificationService;

    /**
     * @var StatusWorkflow|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $statusWorkflow;

    /**
     * @var CommandBus|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $commandBus;

    /**
     * @var MoneyService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $moneyService;

    /**
     * @var OrderRmaRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderRmaRepository;

    /**
     * @var SiteSettingService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $siteSettingService;

    /**
     * @var array
     */
    protected $config;

    public function setUp()
    {
        $this->orderBundleRepository = $this->createMock(OrderBundleRepository::class);
        $this->orderPackageRepository = $this->createMock(OrderPackageRepository::class);
        $this->supplierService = $this->createMock(SupplierService::class);
        $this->userService = $this->createMock(UserService::class);
        $this->orderItemRepository = $this->createMock(OrderItemRepository::class);
        $this->siteSettingRepository = $this->createMock(SiteSettingRepository::class);
        $this->shippingService = $this->createMock(ShippingService::class);
        $this->addressService = $this->createMock(AddressService::class);
        $this->cancelReasonRepository = $this->createMock(CancelReasonRepository::class);
        $this->notificationService = $this->createMock(NotificationService::class);
        $this->statusWorkflow = $this->createMock(StatusWorkflow::class);
        $this->commandBus = $this->createMock(CommandBus::class);
        $this->moneyService = $this->createMock(MoneyService::class);
        $this->orderRmaRepository = $this->createMock(OrderRmaRepository::class);
        $this->siteSettingService = $this->createMock(SiteSettingService::class);
        $this->config = [];

        $this->service = new OrderService(
            $this->orderBundleRepository,
            $this->orderPackageRepository,
            $this->supplierService,
            $this->userService,
            $this->orderItemRepository,
            $this->siteSettingRepository,
            $this->shippingService,
            $this->addressService,
            $this->config,
            $this->cancelReasonRepository,
            $this->notificationService,
            $this->statusWorkflow,
            $this->commandBus,
            $this->moneyService,
            $this->orderRmaRepository,
            $this->siteSettingService
        );
    }

    /**
     * @dataProvider isAllowCancelOrderByCustomerData
     */
    public function testIsAllowCancelOrderByCustomer($expected, $data)
    {
        $items = new ArrayCollection();
        foreach ($data['items'] as $itemStatus) {
            $item = (new OrderItem())->setStatus($itemStatus['status']);
            $items->add($item);
        }
        $packages = new ArrayCollection();
        $package = (new OrderPackage())->setItems($items);
        $packages->add($package);
        $order = (new OrderBundle())->setPackages($packages);

        $this->assertEquals($expected, OrderService::isAllowCancelOrderByCustomer($order));
    }

    public function testIsOrderRmaBelongToCustomer()
    {
        $orderRma = (new OrderRma())->setOrderItem(
            (new OrderItem())->setPackage(
                (new OrderPackage())->setBundle(
                    (new OrderBundle())->setCustomerProfile(
                        (new Customer())->setId(1)
                    )
                )
            )
        );
        $customer = (new Customer())->setId(2);
        $this->assertEquals(false, $this->service->isOrderRmaBelongToCustomer($orderRma, $customer));

        $orderRma = (new OrderRma())->setOrderItem(
            (new OrderItem())->setPackage(
                (new OrderPackage())->setBundle(
                    (new OrderBundle())->setCustomerProfile(
                        (new Customer())->setId(3)
                    )
                )
            )
        );
        $customer = (new Customer())->setId(3);
        $this->assertEquals(true, $this->service->isOrderRmaBelongToCustomer($orderRma, $customer));
    }

    public function testIsOrderItemBelongToCustomer()
    {
        $orderItem = (new OrderItem())->setPackage(
            (new OrderPackage())->setBundle(
                (new OrderBundle())->setCustomerProfile(
                    (new Customer())->setId(1)
                )
            )
        );
        $customer = (new Customer())->setId(2);
        $this->assertEquals(false, $this->service->isOrderItemBelongToCustomer($orderItem, $customer));

        $orderItem = (new OrderItem())->setPackage(
            (new OrderPackage())->setBundle(
                (new OrderBundle())->setCustomerProfile(
                    (new Customer())->setId(3)
                )
            )
        );
        $customer = (new Customer())->setId(3);
        $this->assertEquals(true, $this->service->isOrderItemBelongToCustomer($orderItem, $customer));
    }

    public function isAllowCancelOrderByCustomerData()
    {
        return [
            'test1' => [
                'expected' => false,
                'data' => [
                    'items' => []
                ]
            ],
            'test2' => [
                'expected' => true,
                'data' => [
                    'items' => [
                        ['status' => [Status::TYPE_GENERAL => StatusEnum::PROCESSING]],
                        ['status' => [Status::TYPE_GENERAL => StatusEnum::CANCELLED]],
                    ]
                ]
            ],
            'test3' => [
                'expected' => false,
                'data' => [
                    'items' => [
                        ['status' => [Status::TYPE_GENERAL => StatusEnum::COMPLETE]],
                        ['status' => [Status::TYPE_GENERAL => StatusEnum::PROCESSING]],
                    ]
                ]
            ],
            'test4' => [
                'expected' => true,
                'data' => [
                    'items' => [
                        ['status' => [Status::TYPE_GENERAL => StatusEnum::PROCESSING]],
                        ['status' => [Status::TYPE_GENERAL => StatusEnum::CANCEL_REQUESTED_USER]],
                    ]
                ]
            ],
            'test5' => [
                'expected' => false,
                'data' => [
                    'items' => [
                        ['status' => [Status::TYPE_GENERAL => StatusEnum::SENT_TO_LOGISTICS]],
                        ['status' => [Status::TYPE_GENERAL => StatusEnum::PROCESSING]],
                    ]
                ]
            ],
            'test6' => [
                'expected' => true,
                'data' => [
                    'items' => [
                        ['status' => [Status::TYPE_GENERAL => StatusEnum::PROCESSING]],
                        ['status' => [Status::TYPE_GENERAL => StatusEnum::PROCESSING]],
                    ]
                ]
            ],
            'test7' => [
                'expected' => true,
                'data' => [
                    'items' => [
                        ['status' => [Status::TYPE_GENERAL => StatusEnum::DROPSHIPPED]],
                        ['status' => [Status::TYPE_GENERAL => StatusEnum::PROCESSING]],
                    ]
                ]
            ],
            'test8' => [
                'expected' => false,
                'data' => [
                    'items' => [
                        ['status' => [Status::TYPE_GENERAL => StatusEnum::CANCELLED]],
                        ['status' => [Status::TYPE_GENERAL => StatusEnum::CANCEL_REQUESTED_USER]],
                    ]
                ]
            ],
            'test9' => [
                'expected' => false,
                'data' => [
                    'items' => [
                        ['status' => [Status::TYPE_GENERAL => StatusEnum::COMPLETE]],
                        ['status' => [Status::TYPE_GENERAL => StatusEnum::COMPLETE]],
                    ]
                ]
            ],
        ];
    }

    /**
     * @dataProvider moveBidsData
     */
    public function testMoveBids($expected, $data)
    {
        $orderBundle = new OrderBundle();
        $orderItem1 = (new OrderItem())->setDispatchDate(new \DateTime())->setPackage(
            (new OrderPackage())->setBundle($orderBundle)->setSupplierProfile(new Supplier())
        );
        $orderItem2 = (new OrderItem())->setDispatchDate(new \DateTime())->setPackage(
            (new OrderPackage())->setBundle($orderBundle)->setSupplierProfile(new Supplier())
        );
        $bids = [
            'bid1' => (new OrderBid())->setStatus(OrderBid::STATUS_OPEN),
            'bid2' => (new OrderBid())->setStatus(OrderBid::STATUS_ACCEPTED),
            'bid3' => (new OrderBid())->setStatus(OrderBid::STATUS_REJECTED),
            'bid4' => (new OrderBid())->setStatus(OrderBid::STATUS_MISSED),
            'bid5' => (new OrderBid())->setStatus(OrderBid::STATUS_CANCELLED),
        ];

        foreach ($data['bids'] as $bidKey) {
            $orderItem1->addBid($bids[$bidKey]);
        }

        $this->service->moveBids($orderItem1, $orderItem2, $data['createAccepted']);

        $this->assertEquals($expected['count_bids'], $orderItem2->getBids()->count());
        foreach ($expected['bids'] as $bidKey) {
            $this->assertTrue($orderItem2->getBids()->contains($bids[$bidKey]));
        }
    }

    /**
     * @dataProvider addNoticeAboutCostData
     */
    public function testAddNoticeAboutCost($expected, $data)
    {
        $orderItem1 = (new OrderItem())
            ->setCost($data['item1']['cost'])
            ->setNumber($data['item1']['number'])
            ->setBrand($data['item1']['brand'])
            ->setPrice($data['item1']['price'])
            ->setDeliveryPrice($data['item1']['delivery_price'])
            ->setQty($data['item1']['qty'])
            ->setStatus($data['item1']['status'])
            ->setPackage((new OrderPackage())->setCurrency($data['item1']['currency']));
        $orderItem2 = (new OrderItem())
            ->setCost($data['item2']['cost'])
            ->setNumber($data['item2']['number'])
            ->setBrand($data['item2']['brand'])
            ->setPrice($data['item2']['price'])
            ->setDeliveryPrice($data['item2']['delivery_price'])
            ->setQty($data['item2']['qty'])
            ->setStatus($data['item2']['status'])
            ->setPackage((new OrderPackage())->setCurrency($data['item1']['currency']));

        $this->service->addNoticeAboutCost($orderItem1, $orderItem2, new User());
        $this->assertCount($expected['count_notes'], $orderItem1->getNotes());
        if (!empty($expected['note'])) {
            $this->assertEquals(
                $expected['note'],
                $orderItem1->getNotes()['SALES'][0]['message']
            );
        }
    }

    public function moveBidsData()
    {
        return [
            'test1' => [
                'expected' => [
                    'count_bids' => 0,
                    'bids' => [],
                ],
                'data' => [
                    'bids' => [],
                    'createAccepted' => false
                ]
            ],
            'test2' => [
                'expected' => [
                    'count_bids' => 1,
                    'bids' => [],
                ],
                'data' => [
                    'bids' => [],
                    'createAccepted' => true
                ]
            ],
            'test3' => [
                'expected' => [
                    'count_bids' => 5,
                    'bids' => ['bid1', 'bid2', 'bid3', 'bid4', 'bid5'],
                ],
                'data' => [
                    'count_bids' => 0,
                    'bids' => ['bid1', 'bid2', 'bid3', 'bid4', 'bid5'],
                    'createAccepted' => true
                ]
            ],
            'test4' => [
                'expected' => [
                    'count_bids' => 4,
                    'bids' => ['bid1', 'bid3', 'bid4', 'bid5'],
                ],
                'data' => [
                    'count_bids' => 0,
                    'bids' => ['bid1', 'bid3', 'bid4', 'bid5'],
                    'createAccepted' => false
                ]
            ]
        ];
    }

    public function addNoticeAboutCostData()
    {
        return [
            'test1' => [
                'expected' => ['count_notes' => 0, 'note' => ''],
                'data' => [
                    'item1' => [
                        'cost' => 10025,
                        'status' => [],
                        'currency' => 'INR',
                        'price' => 100025,
                        'number' => 'test_number_1',
                        'brand' => 'test_brand_1',
                        'delivery_price' => 1025,
                        'qty' => 1,
                    ],
                    'item2' => [
                        'cost' => 10025,
                        'status' => [],
                        'currency' => 'INR',
                        'price' => 100025,
                        'number' => 'test_number_1',
                        'brand' => 'test_brand_1',
                        'delivery_price' => 1025,
                        'qty' => 1,
                    ],
                ]
            ],
            'test2' => [
                'expected' => ['count_notes' => 0, 'note' => ''],
                'data' => [
                    'item1' => [
                        'cost' => 10025,
                        'status' => [],
                        'currency' => 'INR',
                        'price' => 100025,
                        'number' => 'test_number_1',
                        'brand' => 'test_brand_1',
                        'delivery_price' => 1025,
                        'qty' => 1,
                    ],
                    'item2' => [
                        'cost' => 10125,
                        'status' => [],
                        'currency' => 'INR',
                        'price' => 100025,
                        'number' => 'test_number_1',
                        'brand' => 'test_brand_1',
                        'delivery_price' => 1025,
                        'qty' => 1,
                    ],
                ]
            ],
            'test3' => [
                'expected' => [
                    'count_notes' => 1,
                    'note' => 'Item was cancelled because of the cost change from 100.25 INR to 101.25 INR.'
                ],
                'data' => [
                    'item1' => [
                        'cost' => 10025,
                        'status' => ['G' => 'CANCELLED'],
                        'currency' => 'INR',
                        'price' => 100025,
                        'number' => 'test_number_1',
                        'brand' => 'test_brand_1',
                        'delivery_price' => 1025,
                        'qty' => 1,
                    ],
                    'item2' => [
                        'cost' => 10125,
                        'status' => [],
                        'currency' => 'INR',
                        'price' => 100025,
                        'number' => 'test_number_1',
                        'brand' => 'test_brand_1',
                        'delivery_price' => 1025,
                        'qty' => 1,
                    ],
                ]
            ],
            'test4' => [
                'expected' => [
                    'count_notes' => 1,
                    'note' => 'Item was cancelled because of the cost change from 200.25 USD to 201.25 USD.'
                ],
                'data' => [
                    'item1' => [
                        'cost' => 20025,
                        'status' => ['G' => 'CANCELLED'],
                        'currency' => 'USD',
                        'price' => 100025,
                        'number' => 'test_number_1',
                        'brand' => 'test_brand_1',
                        'delivery_price' => 1025,
                        'qty' => 1,
                    ],
                    'item2' => [
                        'cost' => 20125,
                        'status' => [],
                        'currency' => 'USD',
                        'price' => 100025,
                        'number' => 'test_number_1',
                        'brand' => 'test_brand_1',
                        'delivery_price' => 1025,
                        'qty' => 1,
                    ],
                ]
            ],
            'test5' => [
                'expected' => [
                    'count_notes' => 0,
                    'note' => ''
                ],
                'data' => [
                    'item1' => [
                        'cost' => 20025,
                        'status' => ['G' => 'CANCELLED'],
                        'currency' => 'USD',
                        'price' => 100025,
                        'number' => 'test_number_1',
                        'brand' => 'test_brand_1',
                        'delivery_price' => 1025,
                        'qty' => 1,
                    ],
                    'item2' => [
                        'cost' => 20025,
                        'status' => [],
                        'currency' => 'USD',
                        'price' => 100025,
                        'number' => 'test_number_1',
                        'brand' => 'test_brand_1',
                        'delivery_price' => 1025,
                        'qty' => 1,
                    ],
                ]
            ],
            'test6' => [
                'expected' => [
                    'count_notes' => 0,
                    'note' => ''
                ],
                'data' => [
                    'item1' => [
                        'cost' => 20025,
                        'status' => [],
                        'currency' => 'USD',
                        'price' => 100025,
                        'number' => 'test_number_1',
                        'brand' => 'test_brand_1',
                        'delivery_price' => 1025,
                        'qty' => 1,
                    ],
                    'item2' => [
                        'cost' => 20125,
                        'status' => ['G' => 'CANCELLED'],
                        'currency' => 'USD',
                        'price' => 100025,
                        'number' => 'test_number_1',
                        'brand' => 'test_brand_1',
                        'delivery_price' => 1025,
                        'qty' => 1,
                    ],
                ]
            ],
            'test7' => [
                'expected' => [
                    'count_notes' => 1,
                    'note' => 'Item was cancelled because of the price change from 1000.25 USD to 1001.25 USD.'
                ],
                'data' => [
                    'item1' => [
                        'cost' => 20025,
                        'status' => ['G' => 'CANCELLED'],
                        'currency' => 'USD',
                        'price' => 100025,
                        'number' => 'test_number_1',
                        'brand' => 'test_brand_1',
                        'delivery_price' => 1025,
                        'qty' => 1,
                    ],
                    'item2' => [
                        'cost' => 20025,
                        'status' => [],
                        'currency' => 'USD',
                        'price' => 100125,
                        'number' => 'test_number_1',
                        'brand' => 'test_brand_1',
                        'delivery_price' => 1025,
                        'qty' => 1,
                    ],
                ]
            ],
            'test8' => [
                'expected' => [
                    'count_notes' => 1,
                    'note' => 'Item was cancelled because of item change from test_brand_1 test_number_1 to test_brand_1 test_number_2.'
                ],
                'data' => [
                    'item1' => [
                        'cost' => 20025,
                        'status' => ['G' => 'CANCELLED'],
                        'currency' => 'USD',
                        'price' => 100025,
                        'number' => 'test_number_1',
                        'brand' => 'test_brand_1',
                        'delivery_price' => 1025,
                        'qty' => 1,
                    ],
                    'item2' => [
                        'cost' => 20025,
                        'status' => [],
                        'currency' => 'USD',
                        'price' => 100025,
                        'number' => 'test_number_2',
                        'brand' => 'test_brand_1',
                        'delivery_price' => 1025,
                        'qty' => 1,
                    ],
                ]
            ],
            'test9' => [
                'expected' => [
                    'count_notes' => 1,
                    'note' => 'Item was cancelled because of item change from test_brand_1 test_number_1 to test_brand_2 test_number_1.'
                ],
                'data' => [
                    'item1' => [
                        'cost' => 20025,
                        'status' => ['G' => 'CANCELLED'],
                        'currency' => 'USD',
                        'price' => 100025,
                        'number' => 'test_number_1',
                        'brand' => 'test_brand_1',
                        'delivery_price' => 1025,
                        'qty' => 1,
                    ],
                    'item2' => [
                        'cost' => 20025,
                        'status' => [],
                        'currency' => 'USD',
                        'price' => 100025,
                        'number' => 'test_number_1',
                        'brand' => 'test_brand_2',
                        'delivery_price' => 1025,
                        'qty' => 1,
                    ],
                ]
            ],
            'test10' => [
                'expected' => [
                    'count_notes' => 1,
                    'note' => 'Item was cancelled because of item change from test_brand_1 test_number_1 to test_brand_2 test_number_2.'
                ],
                'data' => [
                    'item1' => [
                        'cost' => 20025,
                        'status' => ['G' => 'CANCELLED'],
                        'currency' => 'USD',
                        'price' => 100025,
                        'number' => 'test_number_1',
                        'brand' => 'test_brand_1',
                        'delivery_price' => 1025,
                        'qty' => 1,
                    ],
                    'item2' => [
                        'cost' => 20025,
                        'status' => [],
                        'currency' => 'USD',
                        'price' => 100025,
                        'number' => 'test_number_2',
                        'brand' => 'test_brand_2',
                        'delivery_price' => 1025,
                        'qty' => 1,
                    ],
                ]
            ],
            'test11' => [
                'expected' => [
                    'count_notes' => 1,
                    'note' => 'Item was cancelled because of the change of delivery cost from 10.25 USD to 11.25 USD.'
                ],
                'data' => [
                    'item1' => [
                        'cost' => 20025,
                        'status' => ['G' => 'CANCELLED'],
                        'currency' => 'USD',
                        'price' => 100025,
                        'number' => 'test_number_1',
                        'brand' => 'test_brand_1',
                        'delivery_price' => 1025,
                        'qty' => 1,
                    ],
                    'item2' => [
                        'cost' => 20025,
                        'status' => [],
                        'currency' => 'USD',
                        'price' => 100025,
                        'number' => 'test_number_1',
                        'brand' => 'test_brand_1',
                        'delivery_price' => 1125,
                        'qty' => 1,
                    ],
                ]
            ],
            'test12' => [
                'expected' => [
                    'count_notes' => 1,
                    'note' => 'Item was cancelled because of the quantity change from 1 to 2.'
                ],
                'data' => [
                    'item1' => [
                        'cost' => 20025,
                        'status' => ['G' => 'CANCELLED'],
                        'currency' => 'USD',
                        'price' => 100025,
                        'number' => 'test_number_1',
                        'brand' => 'test_brand_1',
                        'delivery_price' => 1026,
                        'qty' => 1,
                    ],
                    'item2' => [
                        'cost' => 20025,
                        'status' => [],
                        'currency' => 'USD',
                        'price' => 100025,
                        'number' => 'test_number_1',
                        'brand' => 'test_brand_1',
                        'delivery_price' => 513,
                        'qty' => 2,
                    ],
                ]
            ],
            'test13' => [
                'expected' => [
                    'count_notes' => 1,
                    'note' =>
                        'Item was cancelled because of item change from test_brand_1 test_number_1 to test_brand_2 test_number_2,'
                        .' the price change from 1000.25 USD to 1001.25 USD, the cost change from 200.25 USD to 201.25 USD,'
                        .' the change of delivery cost from 10.25 USD to 49.5 USD, the quantity change from 1 to 2.'
                ],
                'data' => [
                    'item1' => [
                        'cost' => 20025,
                        'status' => ['G' => 'CANCELLED'],
                        'currency' => 'USD',
                        'price' => 100025,
                        'number' => 'test_number_1',
                        'brand' => 'test_brand_1',
                        'delivery_price' => 1025,
                        'qty' => 1,
                    ],
                    'item2' => [
                        'cost' => 20125,
                        'status' => [],
                        'currency' => 'USD',
                        'price' => 100125,
                        'number' => 'test_number_2',
                        'brand' => 'test_brand_2',
                        'delivery_price' => 2475,
                        'qty' => 2,
                    ],
                ]
            ],
        ];
    }

    /**
     * @dataProvider getTrackPackageDataData
     */
    public function testGetTrackPackageData($expected, $data)
    {
        $this->assertEquals($expected, OrderService::getTrackPackageData($data['package']));
    }

    public function getTrackPackageDataData()
    {
        return [
            'test1' => [
                'expected' => [
                    1 => ['status' => true, 'date' => null, 'show' => true,],
                    2 => ['status' => false, 'date' => null, 'show' => true,],
                    3 => ['status' => false, 'date' => null, 'show' => true,],
                    4 => ['status' => false, 'date' => null, 'show' => true,],
                    5 => ['status' => false, 'date' => null, 'show' => true,],
                    6 => ['status' => false, 'date' => null, 'show' => false,],
                ],
                'data' => [
                    'package' => $this->createConfiguredMock(
                        OrderPackage::class,
                        [
                            'getBundle' => new OrderBundle(),
                            'getShippingETA' => null,
                            'getStatus' => [],
                            'getStatusHistory' => [],
                            'getItems' => (function () {
                                return new ArrayCollection();
                            })(),
                            'getShippingBox' => null,
                            'getCustomerStatusName' => ''
                        ]
                    )
                ],
                'description' => 'empty package'
            ],
            'test2' => [
                'expected' => [
                    1 => ['status' => true, 'date' => new \DateTime('2017-02-02'), 'show' => true,],
                    2 => ['status' => false, 'date' => null, 'show' => true,],
                    3 => ['status' => false, 'date' => null, 'show' => true,],
                    4 => ['status' => false, 'date' => new \DateTime('2017-03-03'), 'show' => true,],
                    5 => ['status' => false, 'date' => new \DateTime('2017-03-10'), 'show' => true,],
                    6 => ['status' => false, 'date' => null, 'show' => false,],
                ],
                'data' => [
                    'package' => $this->createConfiguredMock(
                        OrderPackage::class,
                        [
                            'getBundle' => (new OrderBundle())
                                ->setCreatedAt(new \DateTime('2017-02-02')),
                            'getShippingETA' => new \DateTime('2017-03-10'),
                            'getStatus' => [],
                            'getStatusHistory' => [],
                            'getItems' => (function () {
                                $items = new ArrayCollection();
                                $items->add((new OrderItem())->setDispatchDate(new \DateTime('2017-03-02')));
                                $items->add((new OrderItem())->setDispatchDate(new \DateTime('2017-03-03')));
                                return $items;
                            })()
                        ]
                    )
                ],
                'description' => 'empty package with order and items'
            ],
            'test3' => [
                'expected' => [
                    1 => ['status' => true, 'date' => new \DateTime('2017-02-02'), 'show' => true,],
                    2 => ['status' => false, 'date' => null, 'show' => true,],
                    3 => ['status' => false, 'date' => null, 'show' => true,],
                    4 => ['status' => false, 'date' => new \DateTime('2017-03-03'), 'show' => true,],
                    5 => ['status' => false, 'date' => new \DateTime('2017-03-10'), 'show' => true,],
                    6 => ['status' => false, 'date' => null, 'show' => false,],
                ],
                'data' => [
                    'package' => $this->createConfiguredMock(
                        OrderPackage::class,
                        [
                            'getBundle' => (new OrderBundle())
                                ->setCreatedAt(new \DateTime('2017-02-02')),
                            'getShippingETA' => new \DateTime('2017-03-10'),
                            'getStatus' => [
                                Status::TYPE_GENERAL => StatusEnum::PROCESSING,
                                Status::TYPE_CUSTOMER =>  StatusEnum::CUSTOMER_NEW
                            ],
                            'getStatusHistory' => [],
                            'getItems' => (function () {
                                $items = new ArrayCollection();
                                $items->add((new OrderItem())->setDispatchDate(new \DateTime('2017-03-03')));
                                $items->add((new OrderItem())->setDispatchDate(new \DateTime('2017-03-02')));
                                return $items;
                            })()
                        ]
                    )
                ],
                'description' => 'active steps: 1'
            ],
            'test4' => [
                'expected' => [
                    1 => ['status' => true, 'date' => new \DateTime('2018-02-02'), 'show' => true,],
                    2 => ['status' => true, 'date' => new \DateTime('2018-02-03'), 'show' => true,],
                    3 => ['status' => false, 'date' => null, 'show' => true,],
                    4 => ['status' => false, 'date' => new \DateTime('2018-03-03'), 'show' => true,],
                    5 => ['status' => false, 'date' => new \DateTime('2018-03-10'), 'show' => true,],
                    6 => ['status' => false, 'date' => null, 'show' => false,],
                ],
                'data' => [
                    'package' => $this->createConfiguredMock(
                        OrderPackage::class,
                        [
                            'getBundle' => (new OrderBundle())->setCreatedAt(new \DateTime('2018-02-02')),
                            'getShippingETA' => new \DateTime('2018-03-10'),
                            'getStatus' => [
                                Status::TYPE_GENERAL => StatusEnum::DROPSHIPPED,
                                Status::TYPE_CUSTOMER =>  StatusEnum::CUSTOMER_PROCESSING,
                                Status::TYPE_SUPPLIER =>  StatusEnum::SUPPLIER_NEW,
                            ],
                            'getStatusHistory' => [
                                ['to' => [StatusEnum::CUSTOMER_PROCESSING], 'date' => strtotime('2018-02-02 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_PROCESSING], 'date' => strtotime('2018-02-03 00:00:00')],
                            ],
                            'getItems' => (function () {
                                $items = new ArrayCollection();
                                $items->add((new OrderItem())->setDispatchDate(new \DateTime('2018-03-03')));
                                $items->add((new OrderItem())->setDispatchDate(new \DateTime('2018-03-02')));
                                return $items;
                            })()
                        ]
                    )
                ],
                'description' => 'active steps: 1, 2'
            ],
            'test5' => [
                'expected' => [
                    1 => ['status' => true, 'date' => new \DateTime('2018-02-02'), 'show' => true,],
                    2 => ['status' => true, 'date' => new \DateTime('2018-02-03'), 'show' => true,],
                    3 => ['status' => true, 'date' => new \DateTime('2018-02-13'), 'show' => true,],
                    4 => ['status' => false, 'date' => new \DateTime('2018-03-03'), 'show' => true,],
                    5 => ['status' => false, 'date' => new \DateTime('2018-03-10'), 'show' => true,],
                    6 => ['status' => false, 'date' => null, 'show' => false,],
                ],
                'data' => [
                    'package' => $this->createConfiguredMock(
                        OrderPackage::class,
                        [
                            'getBundle' => (new OrderBundle())->setCreatedAt(new \DateTime('2018-02-02')),
                            'getShippingETA' => new \DateTime('2018-03-10'),
                            'getStatus' => [
                                Status::TYPE_GENERAL => StatusEnum::SENT_TO_LOGISTICS,
                                Status::TYPE_CUSTOMER =>  StatusEnum::CUSTOMER_READY_TO_SEND,
                                Status::TYPE_LOGISTIC =>  StatusEnum::RECEIVED_ON_HUB,
                            ],
                            'getStatusHistory' => [
                                ['to' => [StatusEnum::CUSTOMER_PROCESSING], 'date' => strtotime('2018-02-02 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_PROCESSING], 'date' => strtotime('2018-02-03 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_READY_TO_SEND], 'date' => strtotime('2018-02-12 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_READY_TO_SEND], 'date' => strtotime('2018-02-13 00:00:00')],
                            ],
                            'getItems' => (function () {
                                $items = new ArrayCollection();
                                $items->add((new OrderItem())->setDispatchDate(new \DateTime('2018-03-03')));
                                $items->add((new OrderItem())->setDispatchDate(new \DateTime('2018-03-02')));
                                return $items;
                            })()
                        ]
                    )
                ],
                'description' => 'active steps: 1, 2, 3'
            ],
            'test6' => [
                'expected' => [
                    1 => ['status' => true, 'date' => new \DateTime('2018-02-02'), 'show' => true,],
                    2 => ['status' => true, 'date' => new \DateTime('2018-02-03'), 'show' => true,],
                    3 => ['status' => true, 'date' => new \DateTime('2018-02-13'), 'show' => true,],
                    4 => ['status' => true, 'date' => new \DateTime('2018-03-13'), 'show' => true,],
                    5 => ['status' => false, 'date' => new \DateTime('2018-03-10'), 'show' => true,],
                    6 => ['status' => false, 'date' => null, 'show' => false,],
                ],
                'data' => [
                    'package' => $this->createConfiguredMock(
                        OrderPackage::class,
                        [
                            'getBundle' => (new OrderBundle())->setCreatedAt(new \DateTime('2018-02-02')),
                            'getShippingETA' => new \DateTime('2018-03-10'),
                            'getStatus' => [
                                Status::TYPE_GENERAL => StatusEnum::SENT_TO_LOGISTICS,
                                Status::TYPE_CUSTOMER =>  StatusEnum::CUSTOMER_DISPATCHED,
                                Status::TYPE_LOGISTIC =>  StatusEnum::DISPATCHED,
                            ],
                            'getStatusHistory' => [
                                ['to' => [StatusEnum::CUSTOMER_PROCESSING], 'date' => strtotime('2018-02-02 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_PROCESSING], 'date' => strtotime('2018-02-03 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_READY_TO_SEND], 'date' => strtotime('2018-02-12 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_READY_TO_SEND], 'date' => strtotime('2018-02-13 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_DISPATCHED], 'date' => strtotime('2018-02-22 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_DISPATCHED], 'date' => strtotime('2018-02-23 00:00:00')],
                            ],
                            'getItems' => (function () {
                                $items = new ArrayCollection();
                                $items->add((new OrderItem())->setDispatchDate(new \DateTime('2018-03-03')));
                                $items->add((new OrderItem())->setDispatchDate(new \DateTime('2018-03-02')));
                                return $items;
                            })(),
                            'getShippingBox' => (new ShippingBox())
                                ->setDispatchedAt(new \DateTime('2018-03-13'))
                                ->setDeliveredAt(new \DateTime('2018-03-14'))
                        ]
                    )
                ],
                'description' => 'active steps: 1, 2, 3, 4. date for 4 shippingBox'
            ],
            'test7' => [
                'expected' => [
                    1 => ['status' => true, 'date' => new \DateTime('2018-02-02'), 'show' => true,],
                    2 => ['status' => true, 'date' => new \DateTime('2018-02-03'), 'show' => true,],
                    3 => ['status' => true, 'date' => new \DateTime('2018-02-13'), 'show' => true,],
                    4 => ['status' => true, 'date' => new \DateTime('2018-03-13'), 'show' => true,],
                    5 => ['status' => true, 'date' => new \DateTime('2018-03-14'), 'show' => true,],
                    6 => ['status' => false, 'date' => null, 'show' => false,],
                ],
                'data' => [
                    'package' => $this->createConfiguredMock(
                        OrderPackage::class,
                        [
                            'getBundle' => (new OrderBundle())->setCreatedAt(new \DateTime('2018-02-02')),
                            'getShippingETA' => new \DateTime('2018-03-10'),
                            'getStatus' => [
                                Status::TYPE_GENERAL => StatusEnum::COMPLETE,
                                Status::TYPE_LOGISTIC =>  StatusEnum::DELIVERED,
                            ],
                            'getStatusHistory' => [
                                ['to' => [StatusEnum::CUSTOMER_PROCESSING], 'date' => strtotime('2018-02-02 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_PROCESSING], 'date' => strtotime('2018-02-03 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_READY_TO_SEND], 'date' => strtotime('2018-02-12 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_READY_TO_SEND], 'date' => strtotime('2018-02-13 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_DISPATCHED], 'date' => strtotime('2018-02-22 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_DISPATCHED], 'date' => strtotime('2018-02-23 00:00:00')],
                            ],
                            'getItems' => (function () {
                                $items = new ArrayCollection();
                                $items->add((new OrderItem())->setDispatchDate(new \DateTime('2018-03-03')));
                                $items->add((new OrderItem())->setDispatchDate(new \DateTime('2018-03-02')));
                                return $items;
                            })(),
                            'getShippingBox' => (new ShippingBox())
                                ->setDispatchedAt(new \DateTime('2018-03-13'))
                                ->setDeliveredAt(new \DateTime('2018-03-14'))
                        ]
                    )
                ],
                'description' => 'active steps: 1, 2, 3, 4, 5. date for 4, 5 from shippingBox'
            ],

            'test10' => [
                'expected' => [
                    1 => ['status' => true, 'date' => new \DateTime('2018-02-02'), 'show' => true,],
                    2 => ['status' => true, 'date' => new \DateTime('2018-02-03'), 'show' => true,],
                    3 => ['status' => false, 'date' => null, 'show' => true,],
                    4 => ['status' => false, 'date' => null, 'show' => true,],
                    5 => ['status' => false, 'date' => null, 'show' => true,],
                    6 => ['status' => false, 'date' => null, 'show' => false,],
                ],
                'data' => [
                    'package' => $this->createConfiguredMock(
                        OrderPackage::class,
                        [
                            'getBundle' => (new OrderBundle())->setCreatedAt(new \DateTime('2018-02-02')),
                            'getShippingETA' => new \DateTime('2018-03-10'),
                            'getStatus' => [
                                Status::TYPE_GENERAL => StatusEnum::CANCEL_REQUESTED_USER,
                                Status::TYPE_CUSTOMER =>  StatusEnum::CUSTOMER_PROCESSING,
                                Status::TYPE_SUPPLIER =>  StatusEnum::SUPPLIER_NEW,
                            ],
                            'getStatusHistory' => [
                                ['to' => [StatusEnum::CUSTOMER_PROCESSING], 'date' => strtotime('2018-02-02 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_PROCESSING], 'date' => strtotime('2018-02-03 00:00:00')],
                            ],
                            'getItems' => (function () {
                                $items = new ArrayCollection();
                                $items->add((new OrderItem())->setDispatchDate(new \DateTime('2018-03-03')));
                                $items->add((new OrderItem())->setDispatchDate(new \DateTime('2018-03-02')));
                                return $items;
                            })(),
                            'getCustomerStatusName' => 'Cancel Requested'
                        ]
                    )
                ],
                'description' => 'active steps: 1, 2. cancel requested'
            ],
            'test11' => [
                'expected' => [
                    1 => ['status' => true, 'date' => new \DateTime('2018-02-02'), 'show' => true,],
                    2 => ['status' => true, 'date' => new \DateTime('2018-02-03'), 'show' => true,],
                    3 => ['status' => false, 'date' => null, 'show' => true,],
                    4 => ['status' => false, 'date' => null, 'show' => true,],
                    5 => ['status' => false, 'date' => null, 'show' => true,],
                    6 => ['status' => false, 'date' => null, 'show' => false,],
                ],
                'data' => [
                    'package' => $this->createConfiguredMock(
                        OrderPackage::class,
                        [
                            'getBundle' => (new OrderBundle())->setCreatedAt(new \DateTime('2018-02-02')),
                            'getShippingETA' => new \DateTime('2018-03-10'),
                            'getStatus' => [
                                Status::TYPE_GENERAL => StatusEnum::CANCEL_REQUESTED_USER,
                                Status::TYPE_CUSTOMER =>  StatusEnum::CUSTOMER_READY_TO_SEND,
                                Status::TYPE_LOGISTIC =>  StatusEnum::RECEIVED_ON_HUB,
                            ],
                            'getStatusHistory' => [
                                ['to' => [StatusEnum::CUSTOMER_PROCESSING], 'date' => strtotime('2018-02-02 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_PROCESSING], 'date' => strtotime('2018-02-03 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_READY_TO_SEND], 'date' => strtotime('2018-02-12 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_READY_TO_SEND], 'date' => strtotime('2018-02-13 00:00:00')],
                            ],
                            'getItems' => (function () {
                                $items = new ArrayCollection();
                                $items->add((new OrderItem())->setDispatchDate(new \DateTime('2018-03-03')));
                                $items->add((new OrderItem())->setDispatchDate(new \DateTime('2018-03-02')));
                                return $items;
                            })(),
                            'getCustomerStatusName' => 'Cancel Requested'
                        ]
                    )
                ],
                'description' => 'active steps: 1, 2, 3. cancel requested'
            ],
            'test12' => [
                'expected' => [
                    1 => ['status' => true, 'date' => new \DateTime('2018-02-02'), 'show' => true,],
                    2 => ['status' => true, 'date' => new \DateTime('2018-02-03'), 'show' => true,],
                    3 => ['status' => true, 'date' => new \DateTime('2018-02-13'), 'show' => true,],
                    4 => ['status' => false, 'date' => null, 'show' => true,],
                    5 => ['status' => false, 'date' => null, 'show' => true,],
                    6 => ['status' => false, 'date' => null, 'show' => false,],
                ],
                'data' => [
                    'package' => $this->createConfiguredMock(
                        OrderPackage::class,
                        [
                            'getBundle' => (new OrderBundle())->setCreatedAt(new \DateTime('2018-02-02')),
                            'getShippingETA' => new \DateTime('2018-03-10'),
                            'getStatus' => [
                                Status::TYPE_GENERAL => StatusEnum::CANCEL_REQUESTED_USER,
                                Status::TYPE_CUSTOMER =>  StatusEnum::CUSTOMER_DISPATCHED,
                                Status::TYPE_LOGISTIC =>  StatusEnum::DISPATCHED,
                            ],
                            'getStatusHistory' => [
                                ['to' => [StatusEnum::CUSTOMER_PROCESSING], 'date' => strtotime('2018-02-02 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_PROCESSING], 'date' => strtotime('2018-02-03 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_READY_TO_SEND], 'date' => strtotime('2018-02-12 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_READY_TO_SEND], 'date' => strtotime('2018-02-13 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_DISPATCHED], 'date' => strtotime('2018-02-22 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_DISPATCHED], 'date' => strtotime('2018-02-23 00:00:00')],
                            ],
                            'getItems' => (function () {
                                $items = new ArrayCollection();
                                $items->add((new OrderItem())->setDispatchDate(new \DateTime('2018-03-03')));
                                $items->add((new OrderItem())->setDispatchDate(new \DateTime('2018-03-02')));
                                return $items;
                            })(),
                            'getShippingBox' => (new ShippingBox())
                                ->setDispatchedAt(new \DateTime('2018-03-13'))
                                ->setDeliveredAt(new \DateTime('2018-03-14')),
                            'getCustomerStatusName' => 'Cancel Requested'
                        ]
                    )
                ],
                'description' => 'active steps: 1, 2, 3, 4. date for 4 shippingBox. cancel requested'
            ],

            'test20' => [
                'expected' => [
                    1 => ['status' => true, 'date' => new \DateTime('2017-02-02'), 'show' => true,],
                    2 => ['status' => false, 'date' => null, 'show' => false,],
                    3 => ['status' => false, 'date' => null, 'show' => false,],
                    4 => ['status' => false, 'date' => new \DateTime('2017-03-03'), 'show' => false,],
                    5 => ['status' => false, 'date' => new \DateTime('2017-03-10'), 'show' => false,],
                    6 => ['status' => true, 'date' => new \DateTime('2018-02-02'), 'show' => true,],
                ],
                'data' => [
                    'package' => $this->createConfiguredMock(
                        OrderPackage::class,
                        [
                            'getBundle' => (new OrderBundle())
                                ->setCreatedAt(new \DateTime('2017-02-02')),
                            'getShippingETA' => new \DateTime('2017-03-10'),
                            'getStatus' => [Status::TYPE_GENERAL => StatusEnum::CANCELLED],
                            'getStatusHistory' => [
                                ['to' => [StatusEnum::CANCELLED], 'date' => strtotime('2018-02-02 00:00:00')],
                            ],
                            'getItems' => (function () {
                                $items = new ArrayCollection();
                                $items->add((new OrderItem())->setDispatchDate(new \DateTime('2017-03-03')));
                                $items->add((new OrderItem())->setDispatchDate(new \DateTime('2017-03-02')));
                                return $items;
                            })(),
                            'getCustomerStatusName' => 'Cancelled'
                        ]
                    )
                ],
                'description' => 'active steps: 1. Cancelled'
            ],
            'test21' => [
                'expected' => [
                    1 => ['status' => true, 'date' => new \DateTime('2018-02-02'), 'show' => true,],
                    2 => ['status' => true, 'date' => new \DateTime('2018-02-03'), 'show' => true,],
                    3 => ['status' => false, 'date' => null, 'show' => false,],
                    4 => ['status' => false, 'date' => new \DateTime('2018-03-03'), 'show' => false,],
                    5 => ['status' => false, 'date' => new \DateTime('2018-03-10'), 'show' => false,],
                    6 => ['status' => true, 'date' => new \DateTime('2018-02-02'), 'show' => true,],
                ],
                'data' => [
                    'package' => $this->createConfiguredMock(
                        OrderPackage::class,
                        [
                            'getBundle' => (new OrderBundle())->setCreatedAt(new \DateTime('2018-02-02')),
                            'getShippingETA' => new \DateTime('2018-03-10'),
                            'getStatus' => [Status::TYPE_GENERAL => StatusEnum::CANCELLED],
                            'getStatusHistory' => [
                                ['to' => [StatusEnum::CUSTOMER_PROCESSING], 'date' => strtotime('2018-02-02 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_PROCESSING], 'date' => strtotime('2018-02-03 00:00:00')],
                                ['to' => [StatusEnum::CANCELLED], 'date' => strtotime('2018-02-02 00:00:00')],
                            ],
                            'getItems' => (function () {
                                $items = new ArrayCollection();
                                $items->add((new OrderItem())->setDispatchDate(new \DateTime('2018-03-03')));
                                $items->add((new OrderItem())->setDispatchDate(new \DateTime('2018-03-02')));
                                return $items;
                            })(),
                            'getCustomerStatusName' => 'Cancelled'
                        ]
                    )
                ],
                'description' => 'active steps: 1, 2. Cancelled'
            ],
            'test22' => [
                'expected' => [
                    1 => ['status' => true, 'date' => new \DateTime('2018-02-02'), 'show' => true,],
                    2 => ['status' => true, 'date' => new \DateTime('2018-02-03'), 'show' => true,],
                    3 => ['status' => true, 'date' => new \DateTime('2018-02-13'), 'show' => true,],
                    4 => ['status' => false, 'date' => new \DateTime('2018-03-03'), 'show' => false,],
                    5 => ['status' => false, 'date' => new \DateTime('2018-03-10'), 'show' => false,],
                    6 => ['status' => true, 'date' => new \DateTime('2018-02-02'), 'show' => true,],
                ],
                'data' => [
                    'package' => $this->createConfiguredMock(
                        OrderPackage::class,
                        [
                            'getBundle' => (new OrderBundle())->setCreatedAt(new \DateTime('2018-02-02')),
                            'getShippingETA' => new \DateTime('2018-03-10'),
                            'getStatus' => [Status::TYPE_GENERAL => StatusEnum::CANCELLED],
                            'getStatusHistory' => [
                                ['to' => [StatusEnum::CUSTOMER_PROCESSING], 'date' => strtotime('2018-02-02 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_PROCESSING], 'date' => strtotime('2018-02-03 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_READY_TO_SEND], 'date' => strtotime('2018-02-12 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_READY_TO_SEND], 'date' => strtotime('2018-02-13 00:00:00')],
                                ['to' => [StatusEnum::CANCELLED], 'date' => strtotime('2018-02-02 00:00:00')],
                            ],
                            'getItems' => (function () {
                                $items = new ArrayCollection();
                                $items->add((new OrderItem())->setDispatchDate(new \DateTime('2018-03-03')));
                                $items->add((new OrderItem())->setDispatchDate(new \DateTime('2018-03-02')));
                                return $items;
                            })(),
                            'getCustomerStatusName' => 'Cancelled'
                        ]
                    )
                ],
                'description' => 'active steps: 1, 2, 3. Cancelled'
            ],
            'test23' => [
                'expected' => [
                    1 => ['status' => true, 'date' => new \DateTime('2018-02-02'), 'show' => true,],
                    2 => ['status' => true, 'date' => new \DateTime('2018-02-03'), 'show' => true,],
                    3 => ['status' => true, 'date' => new \DateTime('2018-02-13'), 'show' => true,],
                    4 => ['status' => true, 'date' => new \DateTime('2018-03-13'), 'show' => true,],
                    5 => ['status' => false, 'date' => new \DateTime('2018-03-10'), 'show' => false,],
                    6 => ['status' => true, 'date' => new \DateTime('2018-02-02'), 'show' => true,],
                ],
                'data' => [
                    'package' => $this->createConfiguredMock(
                        OrderPackage::class,
                        [
                            'getBundle' => (new OrderBundle())->setCreatedAt(new \DateTime('2018-02-02')),
                            'getShippingETA' => new \DateTime('2018-03-10'),
                            'getStatus' => [Status::TYPE_GENERAL => StatusEnum::CANCELLED],
                            'getStatusHistory' => [
                                ['to' => [StatusEnum::CUSTOMER_PROCESSING], 'date' => strtotime('2018-02-02 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_PROCESSING], 'date' => strtotime('2018-02-03 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_READY_TO_SEND], 'date' => strtotime('2018-02-12 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_READY_TO_SEND], 'date' => strtotime('2018-02-13 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_DISPATCHED], 'date' => strtotime('2018-02-22 00:00:00')],
                                ['to' => [StatusEnum::CUSTOMER_DISPATCHED], 'date' => strtotime('2018-02-23 00:00:00')],
                                ['to' => [StatusEnum::CANCELLED], 'date' => strtotime('2018-02-02 00:00:00')],
                                ['to' => [], 'date' => strtotime('2018-02-03 00:00:00')],
                                ['to' => null, 'date' => strtotime('2018-02-03 00:00:00')],
                            ],
                            'getItems' => (function () {
                                $items = new ArrayCollection();
                                $items->add((new OrderItem())->setDispatchDate(new \DateTime('2018-03-03')));
                                $items->add((new OrderItem())->setDispatchDate(new \DateTime('2018-03-02')));
                                return $items;
                            })(),
                            'getShippingBox' => (new ShippingBox())
                                ->setDispatchedAt(new \DateTime('2018-03-13'))
                                ->setDeliveredAt(new \DateTime('2018-03-14')),
                            'getCustomerStatusName' => 'Cancelled'
                        ]
                    )
                ],
                'description' => 'active steps: 1, 2, 3, 4. date for 4 shippingBox. Cancelled'
            ],
        ];
    }
}
