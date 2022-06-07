<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Handler;

use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Model\NotifyResult;
use Boodmo\Sales\Model\Workflow\Order\Command\AskForCourierCommand;
use Boodmo\Sales\Model\Workflow\Order\Handler\AskForCourierHandler;
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
 * Class AskForCourierHandlerTest
 */
class AskForCourierHandlerTest extends TestCase
{
    /**
     * @var AskForCourierHandler
     */
    private $handler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AskForCourierCommand
     */
    private $command;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|OrderService
     */
    private $orderService;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityManager;

    /**
     * @var ShippingService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingService;

    /**
     * @var \ReflectionMethod
     */
    private $splitPackageMethod;

    /**
     * @var OrderPackage
     */
    private $package1;

    public function setUp()
    {
        $this->entityManager = $this->createConfiguredMock(
            EntityManager::class,
            ['getConnection' => $this->createMock(Connection::class)]
        );
        $this->shippingService = $this->createMock(ShippingService::class);
        $this->orderService = $this->createMock(OrderService::class);

        $this->handler = new AskForCourierHandler($this->entityManager, $this->shippingService, $this->orderService);
        $this->command = $this->createMock(AskForCourierCommand::class);

        $this->package1 = (new OrderPackage())->setNumber(1)->setId(1);

        $reflector = new \ReflectionObject($this->handler);
        $this->splitPackageMethod = $reflector->getMethod('splitPackage');
        $this->splitPackageMethod->setAccessible(true);
    }

    public function testInvoke()
    {
        $id           = 1;
        $bundle       = new OrderBundle();
        $package      = new OrderPackage();
        $bundle->addPackage($package);

        $this->command->expects($this->at(0))
            ->method('getPackageId')
            ->willReturn($id);

        $this->orderService->expects($this->at(0))
            ->method('loadPackage')
            ->with($id)
            ->willReturn($package);

        ($this->handler)($this->command, $this->orderService);
    }

    public function testInvokePackageException()
    {
        $id           = 1;
        $bundle       = new OrderBundle();
        $package      = new OrderPackage();
        $bundle->addPackage($package);
        $command = new AskForCourierCommand(1);

        $this->orderService->expects($this->at(0))
            ->method('loadPackage')
            ->with($id)
            ->willReturn(null);

        $this->expectExceptionMessage('Internal Server Error: Undefined order package (id: 1)');

        ($this->handler)($command, $this->orderService);
    }

    /**
     * @dataProvider splitPackageData
     */
    public function testSplitPackage($expected, $preInit = null)
    {
        /* @var OrderPackage|null $result */
        if ($preInit && is_callable($preInit)) {
            $preInit($this->package1, $this->getItems());
        }

        $result = $this->splitPackageMethod->invoke($this->handler, $this->package1);

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
                'expected' => ['itemNew1', 'itemConfirmed1', 'itemReadyShippingHub1', 'itemRequestSent1'],
                'preInit' => function (OrderPackage $package, array $items) {
                    $package->setShippingETA(new \DateTime('2018-02-02'));
                    $package->addItem($items['itemNew1']);
                    $package->addItem($items['itemConfirmed1']);
                    $package->addItem($items['itemReadyShipping1']);
                    $package->addItem($items['itemReadyShippingHub1']);
                    $package->addItem($items['itemRequestSent1']);
                },
            ],
            'test3' => [
                'expected' => null,
                'preInit' => function (OrderPackage $package, array $items) {
                    $package->addItem($items['itemReadyShipping1']);
                    $package->addItem($items['itemReadyShipping2']);
                },
            ],
        ];
    }

    /**
     * @return array|OrderItem[]
     */
    protected function getItems(): array
    {
        $newStatus = [
            Status::TYPE_SUPPLIER => StatusEnum::SUPPLIER_NEW,
        ];
        $confirmedStatus = [
            Status::TYPE_SUPPLIER => StatusEnum::CONFIRMED,
        ];
        $readyShippingStatus = [
            Status::TYPE_SUPPLIER => StatusEnum::READY_FOR_SHIPPING,
        ];
        $readyShippingHubStatus = [
            Status::TYPE_SUPPLIER => StatusEnum::READY_FOR_SHIPPING_HUB,
        ];
        $requestSentStatus = [
            Status::TYPE_GENERAL => StatusEnum::SENT_TO_LOGISTICS,
            Status::TYPE_LOGISTIC => StatusEnum::REQUEST_SENT
        ];

        $items = [
            'itemCanceled1' => (new OrderItem())->setStatus([Status::TYPE_GENERAL => StatusEnum::CANCELLED])
                ->setName('itemCanceled1'),
            'itemCanceled2' => (new OrderItem())->setStatus([Status::TYPE_GENERAL => StatusEnum::CANCEL_REQUESTED_USER])
                ->setName('itemCanceled2'),
            'itemCanceled3' => (new OrderItem())->setStatus([
                Status::TYPE_GENERAL => StatusEnum::CANCEL_REQUESTED_SUPPLIER
            ])->setName('itemCanceled3'),
            'itemNew1' => (new OrderItem())->setStatus($newStatus)->setName('itemNew1'),
            'itemNew2' => (new OrderItem())->setStatus($newStatus)->setName('itemNew2'),
            'itemConfirmed1' => (new OrderItem())->setStatus($confirmedStatus)->setName('itemConfirmed1'),
            'itemConfirmed2' => (new OrderItem())->setStatus($confirmedStatus)->setName('itemConfirmed2'),
            'itemReadyShipping1' => (new OrderItem())->setStatus($readyShippingStatus)->setName('itemReadyShipping1'),
            'itemReadyShipping2' => (new OrderItem())->setStatus($readyShippingStatus)->setName('itemReadyShipping2'),
            'itemReadyShippingHub1' => (new OrderItem())->setStatus($readyShippingHubStatus)
                ->setName('itemReadyShippingHub1'),
            'itemReadyShippingHub2' => (new OrderItem())->setStatus($readyShippingHubStatus)
                ->setName('itemReadyShippingHub2'),
            'itemRequestSent1' => (new OrderItem())->setStatus($requestSentStatus)->setName('itemRequestSent1'),
            'itemRequestSent2' => (new OrderItem())->setStatus($requestSentStatus)->setName('itemRequestSent2'),
        ];

        return $items;
    }
}
