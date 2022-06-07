<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Handler;

use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Model\NotifyResult;
use Boodmo\Sales\Model\Workflow\Order\Command\ShipmentReturnPackageCommand;
use Boodmo\Sales\Model\Workflow\Order\Handler\ShipmentReturnPackageHandler;
use Boodmo\Sales\Model\Workflow\Status\InputItemList;
use Boodmo\Sales\Model\Workflow\StatusWorkflow;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Shipping\Entity\ShippingBox;
use Boodmo\User\Entity\User;
use PHPUnit\Framework\TestCase;

class ShipmentReturnPackageHandlerTest extends TestCase
{
    /**
     * @var OrderService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderService;

    /**
     * @var ShipmentReturnPackageCommand
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
     * @var ShipmentReturnPackageHandler
     */
    private $handler;

    /**
     * @var InputItemList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $inputItemList;

    public function setUp()
    {
        $this->statusWorkflow = $this->createPartialMock(
            StatusWorkflow::class,
            ['raiseTransition', 'buildInputItemList']
        );
        $this->orderService = $this->createConfiguredMock(
            OrderService::class,
            ['getStatusWorkflow' => $this->statusWorkflow]
        );

        $this->user = $this->createConfiguredMock(User::class, ['getEmail' => 'test@test.com']);
        $this->inputItemList = $this->createMock(InputItemList::class);
        $this->command = new ShipmentReturnPackageCommand('9806b405-86cd-47c0-8b61-b9d6965935fd', $this->user);

        $this->handler = new ShipmentReturnPackageHandler($this->orderService);
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
            ->method('loadPackage')
            ->willReturn($package);

        $this->orderService->expects($this->at(1))
            ->method('getStatusWorkflow');

        $this->user->expects($this->at(0))
            ->method('getEmail');

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
    }
}
