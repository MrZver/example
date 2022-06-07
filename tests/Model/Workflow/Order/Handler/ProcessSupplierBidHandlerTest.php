<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Handler;

use Boodmo\Catalog\Entity\SupplierPart;
use Boodmo\Catalog\Service\SupplierPartService;
use Boodmo\Core\Service\SiteSettingService;
use Boodmo\Currency\Service\CurrencyService;
use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Entity\OrderBid;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Model\Workflow\Order\Command\ProcessSupplierBidCommand;
use Boodmo\Sales\Model\Workflow\Order\Handler\ProcessSupplierBidHandler;
use Boodmo\Sales\Repository\OrderBidRepository;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Shipping\Entity\Logistics;
use Boodmo\Shipping\Service\ShippingService;
use Boodmo\User\Entity\User;
use Boodmo\User\Entity\UserProfile\Supplier;
use Boodmo\User\Service\SupplierService;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Prooph\ServiceBus\CommandBus;

class ProcessSupplierBidHandlerTest extends TestCase
{
    /**
     * @var ProcessSupplierBidHandler
     */
    protected $handler;

    /**
     * @var OrderService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderService;

    /**
     * @var OrderBidRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderBidRepository;

    /**
     * @var SupplierService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $supplierService;

    /**
     * @var ShippingService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingService;

    /**
     * @var SiteSettingService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $siteSettingService;

    /**
     * @var MoneyService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $moneyService;

    /**
     * @var CommandBus|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $commandBus;

    /**
     * @var SupplierPartService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $supplierPartService;

    /**
     * @var \ReflectionMethod
     */
    protected $allowOpenBidMethod;

    /**
     * @var \ReflectionMethod
     */
    protected $getBidMethod;

    /**
     * @var \ReflectionMethod
     */
    protected $processImproveConditionsMethod;

    public function setUp()
    {
        $this->orderService = $this->createPartialMock(OrderService::class, ['updateItemPrices']);
        $this->orderBidRepository = $this->createPartialMock(OrderBidRepository::class, ['find']);
        $this->supplierService = $this->createPartialMock(SupplierService::class, ['loadSupplierProfile']);
        $this->shippingService = $this->createPartialMock(
            ShippingService::class,
            ['getLogisticsFromSupplierToCustomer']
        );
        $this->siteSettingService = $this->createPartialMock(SiteSettingService::class, ['getSettingByPath']);
        $this->moneyService = $this->getMockBuilder(MoneyService::class)
            ->setConstructorArgs([$this->createConfiguredMock(CurrencyService::class, ['getCurrencyRate' => 65.00])])
            ->setMethods(['getMoney'])
            ->getMock();
        $this->commandBus = $this->createPartialMock(CommandBus::class, ['dispatch']);
        $this->supplierPartService = $this->createPartialMock(SupplierPartService::class, ['prepareNewSupplierPart']);

        $this->orderService->method('updateItemPrices')->willReturnCallback(
            function (OrderItem $item, $price, $cost, $deliveryPrice) {
                return $item->setPrice($price)->setCost($cost)->setDeliveryPrice($deliveryPrice);
            }
        );

        $this->handler = new ProcessSupplierBidHandler(
            $this->orderService,
            $this->orderBidRepository,
            $this->supplierService,
            $this->shippingService,
            $this->siteSettingService,
            $this->moneyService,
            $this->commandBus,
            $this->supplierPartService
        );

        $reflector = new \ReflectionObject($this->handler);
        $this->allowOpenBidMethod = $reflector->getMethod('allowOpenBid');
        $this->allowOpenBidMethod->setAccessible(true);
        $this->getBidMethod = $reflector->getMethod('getBid');
        $this->getBidMethod->setAccessible(true);
        $this->processImproveConditionsMethod = $reflector->getMethod('processImproveConditions');
        $this->processImproveConditionsMethod->setAccessible(true);
    }

    /**
     * @dataProvider getBidData
     */
    public function testGetBid($expected, $data)
    {
        /* @var OrderBid $checkBid */
        /* @var OrderBid $expected */
        $this->orderBidRepository->method('find')->willReturnCallback(function ($id) {
            $map = [
                '07cc660c-4bed-423e-8362-7fa6a6b9955b' => (new OrderBid())
                    ->setId('07cc660c-4bed-423e-8362-7fa6a6b9955b')
                    ->setSupplierProfile((new Supplier())->setId(123))
            ];
            return $map[$id] ?? null;
        });
        $this->supplierService->method('loadSupplierProfile')->willReturnCallback(function ($id) {
            return (new Supplier())->setId($id);
        });
        $this->shippingService->method('getLogisticsFromSupplierToCustomer')->willReturn((new Logistics())->setDays(1));

        $checkBid = $this->getBidMethod->invoke($this->handler, $data['orderItem'], $data['command']);
        //remove dynamic date of note
        if ($notes = $checkBid->getNotes()) {
            unset($notes['BIDS'][0]['date']);
            $checkBid->setNotes($notes);
        }

        $this->assertEquals($expected->getSupplierProfile(), $checkBid->getSupplierProfile());
        $this->assertEquals($expected->getDeliveryDays(), $checkBid->getDeliveryDays());
        $this->assertEquals($expected->getBrand(), $checkBid->getBrand());
        $this->assertEquals($expected->getNumber(), $checkBid->getNumber());
        $this->assertEquals($expected->getGst(), $checkBid->getGst());
        $this->assertEquals($expected->getNotes(), $checkBid->getNotes());
    }

    /**
     * @dataProvider processImproveConditionsData
     */
    public function testProcessImproveConditions($expected, $data)
    {
        $orderItem = (new OrderItem())
            ->setPrice(20026)
            ->setCost(20026)
            ->setDispatchDate(new \DateTime('208-03-03'))
            ->setProductId(123)
            ->setPackage(
                (new OrderPackage())
                    ->setDeliveryDays(2)
                    ->setShippingETA(new \DateTime('208-03-03'))
                    ->setCurrency('INR')
            );
        $this->supplierPartService->method('prepareNewSupplierPart')->willReturn((new SupplierPart())->setId(1));

        $this->commandBus->expects($data['handler'] ? $this->once() : $this->never())->method('dispatch');

        $this->processImproveConditionsMethod->invoke($this->handler, $orderItem, $data['bid'], $data['command']);

        $this->assertEquals($expected['item']['price'], $orderItem->getPrice());
        $this->assertEquals($expected['item']['cost'], $orderItem->getCost());
        $this->assertEquals($expected['item']['dispatchDate'], $orderItem->getDispatchDate());
        $this->assertEquals($expected['item']['productId'], $orderItem->getProductId());
    }

    /**
     * @dataProvider allowOpenBidData
     */
    public function testAllowOpenBid($expected, $data, $description)
    {
        $orderItem = (new OrderItem())->setQty($data['item']['qty'])
            ->setPackage((new OrderPackage())->setCurrency($data['item']['currency']));
        $orderBid = (new OrderBid())->setPrice($data['bid']['price'])
            ->setDispatchDate($data['bid']['dispatchDate']);
        $this->siteSettingService->method('getSettingByPath')->willReturn($data['settings']);
        $this->moneyService->method('getMoney')->willReturnCallback(function ($amount, $currency) {
            return new Money($amount * 100, new Currency($currency));
        });
        $this->assertEquals(
            $expected,
            $this->allowOpenBidMethod->invoke(
                $this->handler,
                $orderItem,
                $orderBid,
                $data['newPrice'],
                $data['newEta']
            ),
            $description
        );
    }

    public function allowOpenBidData()
    {
        return [
            'test1' => [
                'expected' => false,
                'data' => [
                    'item' => ['qty' => 1, 'currency' => 'INR'],
                    'bid' => ['price' => 10025, 'dispatchDate' => new \DateTime('2018-02-02')],
                    'newPrice' => 10025,
                    'newEta' => new \DateTime('2018-02-02'),
                    'settings' => 0
                ],
                'description' => 'Equal: false'
            ],
            'test2' => [
                'expected' => true,
                'data' => [
                    'item' => ['qty' => 1, 'currency' => 'INR'],
                    'bid' => ['price' => 10025, 'dispatchDate' => new \DateTime('2018-02-02')],
                    'newPrice' => 10025,
                    'newEta' => new \DateTime('2018-02-03'),
                    'settings' => 0
                ],
                'description' => 'DispatchDate: true'
            ],
            'test3' => [
                'expected' => true,
                'data' => [
                    'item' => ['qty' => 1, 'currency' => 'INR'],
                    'bid' => ['price' => 10025, 'dispatchDate' => new \DateTime('2018-02-02')],
                    'newPrice' => 20025,
                    'newEta' => new \DateTime('2018-02-02'),
                    'settings' => 0
                ],
                'description' => 'Price: true'
            ],
            'test4' => [
                'expected' => false,
                'data' => [
                    'item' => ['qty' => 1, 'currency' => 'INR'],
                    'bid' => ['price' => 10025, 'dispatchDate' => new \DateTime('2018-02-02')],
                    'newPrice' => 20025,
                    'newEta' => new \DateTime('2018-02-02'),
                    'settings' => 200
                ],
                'description' => 'Price, settings: false'
            ],
            'test5' => [
                'expected' => true,
                'data' => [
                    'item' => ['qty' => 1, 'currency' => 'INR'],
                    'bid' => ['price' => 10025, 'dispatchDate' => new \DateTime('2018-02-02')],
                    'newPrice' => 50025,
                    'newEta' => new \DateTime('2018-02-02'),
                    'settings' => 200
                ],
                'description' => 'Price, settings: true'
            ],
            'test6' => [
                'expected' => true,
                'data' => [
                    'item' => ['qty' => 1, 'currency' => 'INR'],
                    'bid' => ['price' => 10025, 'dispatchDate' => new \DateTime('2018-02-02')],
                    'newPrice' => 50025,
                    'newEta' => new \DateTime('2018-02-03'),
                    'settings' => 200
                ],
                'description' => 'Price, settings, dispatchDate: true'
            ],
            'test7' => [
                'expected' => true,
                'data' => [
                    'item' => ['qty' => 2, 'currency' => 'INR'],
                    'bid' => ['price' => 10026, 'dispatchDate' => new \DateTime('2018-02-02')],
                    'newPrice' => 20126,
                    'newEta' => new \DateTime('2018-02-02'),
                    'settings' => 200
                ],
                'description' => 'Price, settings, qty: true'
            ],
            'test8' => [
                'expected' => false,
                'data' => [
                    'item' => ['qty' => 2, 'currency' => 'INR'],
                    'bid' => ['price' => 10026, 'dispatchDate' => new \DateTime('2018-02-02')],
                    'newPrice' => 20126,
                    'newEta' => new \DateTime('2018-02-02'),
                    'settings' => 400
                ],
                'description' => 'Price, settings, qty: false'
            ]
        ];
    }

    public function getBidData()
    {
        return [
            'test1' => [
                'expected' => (new OrderBid())->setId('07cc660c-4bed-423e-8362-7fa6a6b9955b')
                    ->setPrice(10025)
                    ->setCost(9925)
                    ->setDispatchDate(new \DateTime('2018-02-02'))
                    ->setSupplierProfile((new Supplier())->setId(123)),
                'data' => [
                    'orderItem' => new OrderItem(),
                    'command' => new ProcessSupplierBidCommand(
                        '17cc660c-4bed-423e-8362-7fa6a6b99551',
                        123,
                        10025,
                        9925,
                        new \DateTime('2018-02-02'),
                        new User(),
                        '07cc660c-4bed-423e-8362-7fa6a6b9955b'
                    ),
                ],
                'description' => 'Bid found by id'
            ],
            'test2' => [
                'expected' => (new OrderBid())
                    ->setPrice(10025)
                    ->setCost(9925)
                    ->setDispatchDate(new \DateTime('2018-02-02'))
                    ->setOrderItem(
                        (new OrderItem())->setPackage((new OrderPackage())->setBundle((new OrderBundle())))
                    )
                    ->setSupplierProfile((new Supplier())->setId(123))
                    ->setDeliveryDays(1)
                    ->setBrand(2)
                    ->setNumber('123Number')
                    ->setGst(18)
                    ->setNotes(['BIDS' => [['message' => 'some note1', 'author' => 'test@test.test']]]),
                'data' => [
                    'orderItem' => (new OrderItem())->setPackage((new OrderPackage())->setBundle((new OrderBundle()))),
                    'command' => new ProcessSupplierBidCommand(
                        '17cc660c-4bed-423e-8362-7fa6a6b99551',
                        123,
                        10025,
                        9925,
                        new \DateTime('2018-02-02'),
                        new User(),
                        '08cc660c-4bed-423e-8362-7fa6a6b99558',
                        2,
                        '123Number',
                        18,
                        ['text' => 'some note1', 'author' => 'test@test.test']
                    ),
                ],
                'description' => 'Bid not found by id'
            ],
            'test3' => [
                'expected' => (new OrderBid())
                    ->setPrice(10025)
                    ->setCost(9925)
                    ->setDispatchDate(new \DateTime('2018-02-02'))
                    ->setOrderItem(
                        (new OrderItem())->setPackage((new OrderPackage())->setBundle((new OrderBundle())))
                    )
                    ->setSupplierProfile((new Supplier())->setId(123))
                    ->setDeliveryDays(1)
                    ->setBrand(2)
                    ->setNumber('123Number')
                    ->setGst(18)
                    ->setNotes(['BIDS' => [['message' => 'some note1', 'author' => 'test@test.test']]]),
                'data' => [
                    'orderItem' => (new OrderItem())->setPackage((new OrderPackage())->setBundle((new OrderBundle()))),
                    'command' => new ProcessSupplierBidCommand(
                        '17cc660c-4bed-423e-8362-7fa6a6b99551',
                        123,
                        10025,
                        9925,
                        new \DateTime('2018-02-02'),
                        new User(),
                        null,
                        2,
                        '123Number',
                        18,
                        ['text' => 'some note1', 'author' => 'test@test.test']
                    ),
                ],
                'description' => 'Without bid id'
            ],
        ];
    }

    public function processImproveConditionsData()
    {
        return [
            'test1' => [
                'expected' => [
                    'item' => [
                        'price' => 10025,
                        'cost' => 9925,
                        'dispatchDate' => new \DateTime('2018-02-02'),
                        'productId' => 1
                    ]
                ],
                'data' => [
                    'bid' => (new OrderBid())
                        ->setSupplierProfile((new Supplier())->setId(123))
                        ->setDispatchDate(new \DateTime('2018-02-02')),
                    'command' => new ProcessSupplierBidCommand(
                        '17cc660c-4bed-423e-8362-7fa6a6b99551',
                        123,
                        10025,
                        9925,
                        new \DateTime('2018-02-02'),
                        new User()
                    ),
                    'handler' => null
                ]
            ],
            'test2' => [
                'expected' => [
                    'item' => [
                        'price' => 10025,
                        'cost' => 9925,
                        'dispatchDate' => new \DateTime('2018-02-02'),
                        'productId' => 1
                    ]
                ],
                'data' => [
                    'bid' => (new OrderBid())
                        ->setSupplierProfile((new Supplier())->setId(123))
                        ->setDispatchDate(new \DateTime('2018-02-02')),
                    'command' => new ProcessSupplierBidCommand(
                        '17cc660c-4bed-423e-8362-7fa6a6b99551',
                        123,
                        10025,
                        9925,
                        new \DateTime('2018-02-02'),
                        new User(),
                        null,
                        null,
                        null,
                        null,
                        null,
                        'shipping_ready'
                    ),
                    'handler' => 'shipping_ready'
                ]
            ],
            'test3' => [
                'expected' => [
                    'item' => [
                        'price' => 10025,
                        'cost' => 9925,
                        'dispatchDate' => new \DateTime('2018-02-02'),
                        'productId' => 1
                    ]
                ],
                'data' => [
                    'bid' => (new OrderBid())
                        ->setSupplierProfile((new Supplier())->setId(123))
                        ->setDispatchDate(new \DateTime('2018-02-02')),
                    'command' => new ProcessSupplierBidCommand(
                        '17cc660c-4bed-423e-8362-7fa6a6b99551',
                        123,
                        10025,
                        9925,
                        new \DateTime('2018-02-02'),
                        new User(),
                        null,
                        null,
                        null,
                        null,
                        null,
                        'shipping_ready_hub'
                    ),
                    'handler' => 'shipping_ready_hub'
                ]
            ],
            'test4' => [
                'expected' => [
                    'item' => [
                        'price' => 10025,
                        'cost' => 9925,
                        'dispatchDate' => new \DateTime('2018-02-02'),
                        'productId' => 1
                    ]
                ],
                'data' => [
                    'bid' => (new OrderBid())
                        ->setSupplierProfile((new Supplier())->setId(123))
                        ->setDispatchDate(new \DateTime('2018-02-02')),
                    'command' => new ProcessSupplierBidCommand(
                        '17cc660c-4bed-423e-8362-7fa6a6b99551',
                        123,
                        10025,
                        9925,
                        new \DateTime('2018-02-02'),
                        new User(),
                        null,
                        null,
                        null,
                        null,
                        null,
                        'other_handler'
                    ),
                    'handler' => null
                ]
            ]
        ];
    }
}
