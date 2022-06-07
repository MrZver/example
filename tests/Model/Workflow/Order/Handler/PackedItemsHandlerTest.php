<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Handler;

use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Model\NotifyResult;
use Boodmo\Sales\Model\Workflow\Order\Command\PackedItemsCommand;
use Boodmo\Sales\Model\Workflow\Order\Handler\PackedItemsHandler;
use Boodmo\Sales\Model\Workflow\Status\InputItemList;
use Boodmo\Sales\Model\Workflow\Status\Status;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Model\Workflow\StatusWorkflow;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Shipping\Entity\ShippingBox;
use Boodmo\Shipping\Service\ShippingService;
use Boodmo\User\Entity\User;
use Boodmo\User\Entity\UserProfile\Supplier;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

/**
 * Class PackedItemsHandlerTest
 * @package Boodmo\SalesTest\Model\Workflow\Order\Handler
 */
class PackedItemsHandlerTest extends TestCase
{
    /**
     * @var PackedItemsHandler
     */
    private $handler;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PackedItemsCommand
     */
    private $command;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|OrderService
     */
    private $orderService;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|StatusWorkflow
     */
    private $statusWorkflow;

    /**
     * @var InputItemList
     */
    private $inputItemList;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityManager;

    /**
     * @var ShippingService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingService;

    /**
     * @var User|\PHPUnit_Framework_MockObject_MockObject
     */
    private $user;

    /**
     * @var \ReflectionMethod
     */
    private $splitPackageMethod;

    /**
     * @var \ReflectionMethod
     */
    private $getPackagesMethod;

    /**
     * @var OrderPackage
     */
    private $package1;

    /**
     * @var OrderPackage
     */
    private $package2;

    /**
     * @var Supplier|\PHPUnit_Framework_MockObject_MockObject
     */
    private $supplier;

    public function setUp()
    {
        $this->entityManager = $this->createConfiguredMock(
            EntityManager::class,
            ['getConnection' => $this->createMock(Connection::class)]
        );
        $this->shippingService = $this->createMock(ShippingService::class);
        $this->orderService = $this->createMock(OrderService::class);
        $this->supplier = $this->createConfiguredMock(Supplier::class, ['getBaseCurrency' => 'INR']);

        $this->handler = new PackedItemsHandler($this->entityManager, $this->shippingService, $this->orderService);
        $this->command = $this->createMock(PackedItemsCommand::class);
        $this->statusWorkflow = $this->createMock(StatusWorkflow::class);
        $this->inputItemList  = $this->createMock(InputItemList::class);
        $this->user = $this->createConfiguredMock(User::class, ['getEmail' => 'test@test.com']);

        $this->package1 = (new OrderPackage())->setNumber(1)->setId(1)->setCurrency('INR')
            ->setSupplierProfile($this->supplier);
        $this->package2 = (new OrderPackage())->setNumber(2)->setId(2)->setCurrency('INR')
            ->setSupplierProfile($this->supplier);

        $reflector = new \ReflectionObject($this->handler);
        $this->splitPackageMethod = $reflector->getMethod('splitPackage');
        $this->splitPackageMethod->setAccessible(true);
        $this->getPackagesMethod = $reflector->getMethod('getPackages');
        $this->getPackagesMethod->setAccessible(true);
    }

    public function testInvoke()
    {
        $id           = (string) Uuid::uuid4();
        $shippingBoxId = (string) Uuid::uuid4();
        $bundle       = new OrderBundle();
        $package      = new OrderPackage();
        $orderItem    = new OrderItem();
        $notifyResult = new NotifyResult();
        $shippingBox  = new ShippingBox();

        $orderItem->setId($id);
        $package->addItem($orderItem);
        $bundle->addPackage($package);
        $shippingBox->addPackage($package)->setId($shippingBoxId);

        $this->orderService->expects($this->at(0))
            ->method('getStatusWorkflow')
            ->willReturn($this->statusWorkflow);

        $this->command->expects($this->at(0))
            ->method('getEditor')
            ->willReturn($this->user);

        $this->command->expects($this->at(1))
            ->method('getShippingBoxId')
            ->willReturn($shippingBoxId);

        $this->command->expects($this->at(2))
            ->method('getItems')
            ->willReturn([]);

        $this->command->expects($this->at(3))
            ->method('getItemsIds')
            ->willReturn([$id]);

        $this->orderService->expects($this->at(1))
            ->method('loadOrderItem')
            ->with($id)
            ->willReturn($orderItem);

        $this->statusWorkflow->expects($this->at(0))
            ->method('buildInputItemList')
            ->with([$orderItem])
            ->willReturn($this->inputItemList);

        $this->statusWorkflow->expects($this->at(1))
            ->method('raiseTransition')
            ->withAnyParameters()
            ->willReturn($notifyResult);

        $this->command->expects($this->at(4))
            ->method('getShipmentParams')
            ->willReturn(['width' => 100, 'height' => 100]);

        $this->orderService->expects($this->at(2))
            ->method('save')
            ->with($bundle);

        $this->orderService->expects($this->at(3))
            ->method('triggerNotification')
            ->with($notifyResult);

        ($this->handler)($this->command, $this->orderService);

        $this->assertEquals(
            ['width' => '100', 'height' => '100'],
            ['width' => $package->getShippingBox()->getWidth(), 'height' => $package->getShippingBox()->getHeight()]
        );
    }

    /**
     * @dataProvider splitPackageData
     */
    public function testSplitPackage($expected, $preInit = null, $itemsData = [])
    {
        /* @var OrderPackage|null $result */
        if ($preInit && is_callable($preInit)) {
            $preInit($this->package1, $this->getItems());
        }

        $result = $this->splitPackageMethod->invoke($this->handler, $this->package1, $itemsData);

        if ($expected === null) {
            $this->assertEquals($expected, $result);
        } else {
            $this->assertInstanceOf(OrderPackage::class, $result);

            $itemsInPackage = [];
            foreach ($result->getItems() as $item) {
                $itemsInPackage[] = $item->getName();
            }

            $this->assertEquals($expected, $itemsInPackage, 'Expected orderItems were not found');
        }
    }

    public function testGetPackages()
    {
        $this->orderService->method('loadOrderItem')->will($this->returnCallback(function ($id) {
            $data = [
                1 => (new OrderItem())->setPackage($this->package1),
                2 => (new OrderItem())->setPackage($this->package1),
                3 => (new OrderItem())->setPackage($this->package1),
                4 => (new OrderItem())->setPackage($this->package1),
                5 => (new OrderItem())->setPackage($this->package2),
            ];
            return $data[$id];
        }));

        $this->assertEquals(
            [],
            $this->getPackagesMethod->invoke($this->handler, [], $this->orderService)
        );
        $this->assertEquals(
            [1 => $this->package1],
            $this->getPackagesMethod->invoke($this->handler, [1], $this->orderService)
        );
        $this->assertEquals(
            [1 => $this->package1],
            $this->getPackagesMethod->invoke($this->handler, [2, 3], $this->orderService)
        );
        $this->assertEquals(
            [1 => $this->package1, 2 => $this->package2],
            $this->getPackagesMethod->invoke($this->handler, [4, 5], $this->orderService)
        );
    }

    public function splitPackageData()
    {
        return [
            'test1' => [
                'expected' => null,
                'preInit' => function (OrderPackage $package, array $items) {
                    $package->addItem($items['itemCanceled1']);
                    $package->addItem($items['itemCanceled2']);
                    $package->addItem($items['itemCanceled3']);
                }
            ],
            'test2' => [
                'expected' => ['itemPick1', 'itemRequestSent1'],
                'preInit' => function (OrderPackage $package, array $items) {
                    $package->addItem($items['itemPick1']);
                    $package->addItem($items['itemPack1']);
                    $package->addItem($items['itemRequestSent1']);
                },
                'itemsData' => [
                    '6b09b9c8-38c8-42b8-bcab-1e9385257af6' => ['qty' => 4], //itemPack1
                ]
            ],
            'test3' => [
                'expected' => ['itemPick1', 'itemHub1'],
                'preInit' => function (OrderPackage $package, array $items) {
                    $package->addItem($items['itemPick1']);
                    $package->addItem($items['itemPack1']);
                    $package->addItem($items['itemHub1']);
                },
                'itemsData' => [
                    '6b09b9c8-38c8-42b8-bcab-1e9385257af6' => ['qty' => 4], //itemPack1
                ]
            ],
            'test4' => [
                'expected' => ['itemPick1', 'itemHub1', 'itemCanceled1', 'itemCanceled2', 'itemCanceled3'],
                'preInit' => function (OrderPackage $package, array $items) {
                    $package->addItem($items['itemPick1']);
                    $package->addItem($items['itemPack1']);
                    $package->addItem($items['itemHub1']);
                    $package->addItem($items['itemCanceled1']);
                    $package->addItem($items['itemCanceled2']);
                    $package->addItem($items['itemCanceled3']);
                },
                'itemsData' => [
                    '6b09b9c8-38c8-42b8-bcab-1e9385257af6' => ['qty' => 4], //itemPack1
                ]
            ],
            'test5' => [
                'expected' => [
                    'itemPick1',
                    'itemPick2',
                    'itemHub1',
                    'itemCanceled1',
                    'itemCanceled2',
                    'itemCanceled3',
                    'itemRequestSent1'
                ],
                'preInit' => function (OrderPackage $package, array $items) {
                    $package->addItem($items['itemPick1']);
                    $package->addItem($items['itemPick2']);
                    $package->addItem($items['itemPack1']);
                    $package->addItem($items['itemPack2']);
                    $package->addItem($items['itemHub1']);
                    $package->addItem($items['itemCanceled1']);
                    $package->addItem($items['itemCanceled2']);
                    $package->addItem($items['itemCanceled3']);
                    $package->addItem($items['itemRequestSent1']);
                },
                'itemsData' => [
                    '6b09b9c8-38c8-42b8-bcab-1e9385257af6' => ['qty' => 4], //itemPack1
                    '7b09b9c8-38c8-42b8-bcab-1e9385257af7' => ['qty' => 4], //itemPack2
                ]
            ],
            'test6' => [
                'expected' => ['itemPick1', 'itemHub1', 'itemCanceled1', 'itemCanceled2', 'itemCanceled3', 'itemPack1'],
                'preInit' => function (OrderPackage $package, array $items) {
                    $package->addItem($items['itemPick1']);
                    $package->addItem($items['itemPack1']);
                    $package->addItem($items['itemHub1']);
                    $package->addItem($items['itemCanceled1']);
                    $package->addItem($items['itemCanceled2']);
                    $package->addItem($items['itemCanceled3']);
                },
                'itemsData' => [
                    '6b09b9c8-38c8-42b8-bcab-1e9385257af6' => ['qty' => 2], //itemPack1
                ]
            ],
            'test7' => [
                'expected' => ['itemPick1', 'itemHub1', 'itemCanceled1', 'itemCanceled2', 'itemCanceled3', 'itemPack1'],
                'preInit' => function (OrderPackage $package, array $items) {
                    $package->addItem($items['itemPick1']);
                    $package->addItem($items['itemPack1']);
                    $package->addItem($items['itemHub1']);
                    $package->addItem($items['itemCanceled1']);
                    $package->addItem($items['itemCanceled2']);
                    $package->addItem($items['itemCanceled3']);
                },
                'itemsData' => [
                    '6b09b9c8-38c8-42b8-bcab-1e9385257af6' => ['qty' => 0], //itemPack1
                ]
            ],
        ];
    }

    /**
     * @return array|OrderItem[]
     */
    protected function getItems(): array
    {
        $pickStatus = [
            Status::TYPE_SUPPLIER => StatusEnum::READY_FOR_SHIPPING_HUB,
            Status::TYPE_GENERAL => StatusEnum::DROPSHIPPED,
        ];
        $packStatus = [
            Status::TYPE_GENERAL => StatusEnum::SENT_TO_LOGISTICS,
            Status::TYPE_LOGISTIC => StatusEnum::RECEIVED_ON_HUB,
        ];
        $hubStatus = [
            Status::TYPE_GENERAL => StatusEnum::SENT_TO_LOGISTICS,
            Status::TYPE_LOGISTIC => StatusEnum::SHIPMENT_NEW_HUB,
        ];
        $requestSentStatus = [
            Status::TYPE_GENERAL => StatusEnum::SENT_TO_LOGISTICS,
            Status::TYPE_LOGISTIC => StatusEnum::REQUEST_SENT
        ];

        $items = [
            'itemCanceled1' => (new OrderItem())->setStatus([
                Status::TYPE_GENERAL => StatusEnum::CANCELLED
            ])->setName('itemCanceled1')->setQty(4)->setId('1b09b9c8-38c8-42b8-bcab-1e9385257af1')->setDispatchDate(new \DateTime()),
            'itemCanceled2' => (new OrderItem())->setStatus([
                Status::TYPE_GENERAL => StatusEnum::CANCEL_REQUESTED_USER
            ])->setName('itemCanceled2')->setQty(4)->setId('2b09b9c8-38c8-42b8-bcab-1e9385257af2')->setDispatchDate(new \DateTime()),
            'itemCanceled3' => (new OrderItem())->setStatus([
                Status::TYPE_GENERAL => StatusEnum::CANCEL_REQUESTED_SUPPLIER
            ])->setName('itemCanceled3')->setQty(4)->setId('3b09b9c8-38c8-42b8-bcab-1e9385257af3')->setDispatchDate(new \DateTime()),
            'itemPick1' => (new OrderItem())->setStatus($pickStatus)->setName('itemPick1')->setQty(4)
                ->setId('4b09b9c8-38c8-42b8-bcab-1e9385257af4')->setDispatchDate(new \DateTime()),
            'itemPick2' => (new OrderItem())->setStatus($pickStatus)->setName('itemPick2')->setQty(4)
                ->setId('5b09b9c8-38c8-42b8-bcab-1e9385257af5')->setDispatchDate(new \DateTime()),
            'itemPack1' => (new OrderItem())->setStatus($packStatus)->setName('itemPack1')->setQty(4)
                ->setId('6b09b9c8-38c8-42b8-bcab-1e9385257af6')->setDispatchDate(new \DateTime()),
            'itemPack2' => (new OrderItem())->setStatus($packStatus)->setName('itemPack2')->setQty(4)
                ->setId('7b09b9c8-38c8-42b8-bcab-1e9385257af7')->setDispatchDate(new \DateTime()),
            'itemHub1' => (new OrderItem())->setStatus($hubStatus)->setName('itemHub1')->setQty(4)
                ->setId('8b09b9c8-38c8-42b8-bcab-1e9385257af8')->setDispatchDate(new \DateTime()),
            'itemHub2' => (new OrderItem())->setStatus($hubStatus)->setName('itemHub2')->setQty(4)
                ->setId('9b09b9c8-38c8-42b8-bcab-1e9385257af9')->setDispatchDate(new \DateTime()),
            'itemRequestSent1' => (new OrderItem())->setStatus($requestSentStatus)->setName('itemRequestSent1')
                ->setQty(4)->setId('1009b9c8-38c8-42b8-bcab-1e9385257a10')->setDispatchDate(new \DateTime()),
        ];

        return $items;
    }
}
