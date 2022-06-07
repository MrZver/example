<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Handler;

use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Model\NotifyResult;
use Boodmo\Sales\Model\Workflow\Order\Command\WarehouseInItemsCommand;
use Boodmo\Sales\Model\Workflow\Order\Handler\WarehouseInItemsHandler;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\InputItemList;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Model\Workflow\StatusWorkflow;
use Boodmo\Sales\Service\OrderService;
use Boodmo\User\Entity\User;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

/**
 * Class WarehouseInItemsHandlerTest
 * @package Boodmo\SalesTest\Model\Workflow\Order\Handler
 * @coversDefaultClass \Boodmo\Sales\Model\Workflow\Order\Handler\WarehouseInItemsHandler
 */
class WarehouseInItemsHandlerTest extends TestCase
{
    /**
     * @var WarehouseInItemsHandler
     */
    private $handler;
    /**
     * @var WarehouseInItemsCommand|\PHPUnit_Framework_MockObject_MockObject|WarehouseInItemsCommand
     */
    private $command;
    /**
     * @var OrderService|\PHPUnit_Framework_MockObject_MockObject|OrderService
     */
    private $orderService;
    /**
     * @var StatusWorkflow|\PHPUnit_Framework_MockObject_MockObject|StatusWorkflow
     */
    private $statusWorkflow;

    /**
     * @var InputItemList
     */
    private $inputItemList;

    public function setUp()
    {
        $this->orderService = $this->createMock(OrderService::class);

        $this->handler = new WarehouseInItemsHandler($this->orderService);
        $this->command = $this->createMock(WarehouseInItemsCommand::class);
        $this->statusWorkflow = $this->createMock(StatusWorkflow::class);
        $this->inputItemList = $this->createMock(InputItemList::class);
    }

    /**
     *  @dataProvider getDataProvider
     */
    public function testEqualAcceptedExpected($id, $accepted, $qty, $expected)
    {
        $bundle       = new OrderBundle();
        $package      = new OrderPackage();
        $orderItem    = new OrderItem();
        $notifyResult = new NotifyResult();

        $orderItem->setId($id);
        $orderItem->setQty($qty);
        $package->addItem($orderItem);
        $bundle->addPackage($package);

        $this->orderService->expects($this->at(0))
            ->method('getStatusWorkflow')
            ->willReturn($this->statusWorkflow);

        $this->command->expects($this->at(0))
            ->method('getItemsIds')
            ->willReturn([$id]);

        $this->orderService->expects($this->at(1))
            ->method('loadOrderItem')
            ->with($id)
            ->willReturn($orderItem);

        $this->command->expects($this->at(1))
            ->method('getAcceptedList')
            ->willReturn([$id => $accepted]);

        $this->command->expects($this->at(2))
            ->method('getEditor')
            ->willReturn((new User())->setEmail('test@test.com'));

        $this->statusWorkflow->expects($this->at(0))
            ->method('buildInputItemList')
            ->with([$orderItem])
            ->willReturn($this->inputItemList);

        $this->statusWorkflow->expects($this->at(1))
            ->method('raiseTransition')
            ->withAnyParameters()
            ->willReturn($notifyResult);

        $this->orderService->expects($this->at(2))
            ->method('triggerNotification')
            ->with($notifyResult);

        $this->orderService->expects($this->at(3))
            ->method('save')
            ->with($bundle);

        ($this->handler)($this->command, $this->orderService);

        $this->assertEquals($expected, $orderItem->getQty());
    }

    public function getDataProvider()
    {
        return [
            [
                'id'       => (string)Uuid::uuid4(),
                'accepted' => 1,
                'qty'      => 1,
                'expected' => 1
            ],
            [
                'id'       => (string)Uuid::uuid4(),
                'accepted' => 2,
                'qty'      => 4,
                'expected' => 2
            ]
        ];
    }
}
