<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Handler;

use Boodmo\Sales\Model\Workflow\Status\InputItemList;
use Boodmo\Sales\Model\Workflow\StatusWorkflow;
use Boodmo\Sales\Entity\CancelReason;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Model\NotifyResult;
use Boodmo\Sales\Model\Workflow\Order\Command\ShipmentRejectBoxCommand;
use Boodmo\Sales\Model\Workflow\Order\Handler\ShipmentRejectBoxHandler;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Shipping\Entity\ShippingBox;
use Boodmo\Shipping\Service\ShippingService;
use Boodmo\User\Entity\User;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;

class ShipmentRejectBoxHandlerTest extends TestCase
{
    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityManager;

    /**
     * @var ShippingService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingService;

    /**
     * @var OrderService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderService;

    /**
     * @var InputItemList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $inputItemList;

    /**
     * @var ShipmentRejectBoxCommand
     */
    private $command;

    /**
     * @var User|\PHPUnit_Framework_MockObject_MockObject
     */
    private $user;

    /**
     * @var StatusWorkflow|\PHPUnit_Framework_MockObject_MockObject
     */
    private $statusWorkflow;

    /**
     * @var ShipmentRejectBoxHandler
     */
    private $handler;

    public function setUp()
    {
        $this->entityManager = $this->createConfiguredMock(
            EntityManager::class,
            ['getConnection' => $this->createMock(Connection::class)]
        );
        $this->shippingService = $this->createPartialMock(ShippingService::class, ['loadShippingBox']);
        $this->statusWorkflow = $this->createPartialMock(
            StatusWorkflow::class,
            ['raiseTransition', 'buildInputItemList']
        );
        $this->orderService = $this->createConfiguredMock(
            OrderService::class,
            ['getStatusWorkflow' => $this->statusWorkflow]
        );
        $this->user = $this->createConfiguredMock(User::class, ['getEmail' => 'test@test.com']);
        $this->command = new ShipmentRejectBoxCommand('9806b405-86cd-47c0-8b61-b9d6965935fd', $this->user, 5);
        $this->inputItemList = $this->createMock(InputItemList::class);

        $this->handler = new ShipmentRejectBoxHandler(
            $this->entityManager,
            $this->shippingService,
            $this->orderService
        );
    }

    public function testInvoke()
    {
        $notifyResult = new NotifyResult();
        $shippingBox  = new ShippingBox();
        $bundle  = new OrderBundle();
        $package  = new OrderPackage();
        $item = new OrderItem();
        $package->addItem($item);
        $bundle->addPackage($package);
        $shippingBox->addPackage($package);

        $this->orderService->expects($this->at(0))
            ->method('getStatusWorkflow');

        $this->user->expects($this->at(0))
            ->method('getEmail');

        $this->shippingService->expects($this->at(0))
            ->method('loadShippingBox')
            ->willReturn($shippingBox);

        $this->orderService->expects($this->at(1))
            ->method('loadSalesCancelReason')
            ->with(CancelReason::CANT_DELIVER)
            ->willReturn((new CancelReason())->setId(CancelReason::CANT_DELIVER));

        $this->statusWorkflow->expects($this->at(0))
            ->method('buildInputItemList')
            ->with([$item])
            ->willReturn($this->inputItemList);

        $this->statusWorkflow->expects($this->at(1))
            ->method('raiseTransition')
            ->withAnyParameters()
            ->willReturn($notifyResult);

        $this->orderService->expects($this->at(2))
            ->method('save')->with($bundle);

        $this->orderService->expects($this->at(3))
            ->method('triggerNotification')
            ->with($notifyResult);

        ($this->handler)($this->command, $this->orderService);
        $this->assertEquals($item->getCancelReason()->getId(), CancelReason::CANT_DELIVER);
    }
}
