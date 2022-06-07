<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Handler;

use Boodmo\Catalog\Entity\Brand;
use Boodmo\Catalog\Entity\Family;
use Boodmo\Catalog\Entity\Part;
use Boodmo\Catalog\Entity\SupplierPart;
use Boodmo\Catalog\Service\SupplierPartService;
use Boodmo\Currency\Service\CurrencyService;
use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Entity\CancelReason;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Model\NotifyResult;
use Boodmo\Sales\Model\Workflow\Order\Command\EditItemCommand;
use Boodmo\Sales\Model\Workflow\Order\Handler\EditItemHandler;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\InputItemList;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Model\Workflow\StatusWorkflow;
use Boodmo\Sales\Service\OrderService;
use Boodmo\User\Entity\User;
use Boodmo\User\Entity\UserProfile\Supplier;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

/**
 * Class EditItemHandlerTest
 * @package Boodmo\SalesTest\Model\Workflow\Order\Handler
 * @coversDefaultClass \Boodmo\Sales\Model\Workflow\Order\Handler\EditItemHandler
 */
class EditItemHandlerTest extends TestCase
{

    /**
     * @var EditItemHandler
     */
    private $handler;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EditItemCommand
     */
    private $command;
    /**
     * @var OrderService|\PHPUnit_Framework_MockObject_MockObject|OrderService
     */
    private $orderService;

    /**
     * @var SupplierPartService|\PHPUnit_Framework_MockObject_MockObject|OrderService
     */
    private $supplierPartService;

    /**
     * @var MoneyService|\PHPUnit_Framework_MockObject_MockObject|OrderService
     */
    private $moneyService;

    /**
      * @var \PHPUnit_Framework_MockObject_MockObject|StatusWorkflow
      */
    private $statusWorkflow;
    private $inputItemList;

    public function setUp()
    {
        $this->orderService = $this->createMock(OrderService::class);
        $this->supplierPartService = $this->createPartialMock(SupplierPartService::class, ['prepareNewSupplierPart']);
        $this->moneyService = $this->getMockBuilder(MoneyService::class)
            ->setConstructorArgs([$this->createConfiguredMock(CurrencyService::class, ['getCurrencyRate' => 65.00])])
            ->setMethods(['getMoney', 'convert'])
            ->getMock();

        $this->handler = new EditItemHandler($this->orderService, $this->supplierPartService, $this->moneyService);
        $this->command = $this->createMock(EditItemCommand::class);
        $this->statusWorkflow = $this->createMock(StatusWorkflow::class);
        $this->inputItemList = $this->createMock(InputItemList::class);
    }

    public function testInvoke()
    {
        $id = (string) Uuid::uuid4();
        $bundle = new OrderBundle();
        $package = ((new OrderPackage())->setCurrency('INR'))
            ->setSupplierProfile((new Supplier())->setBaseCurrency('INR')->setUserInfo((new User())->setId(2)));
        $orderItem = new OrderItem();
        $orderItem->setId($id);
        $package->addItem($orderItem);
        $bundle->addPackage($package);
        $notifyResult = new NotifyResult();
        $reason = new CancelReason();
        $this->supplierPartService->method('prepareNewSupplierPart')->willReturn(
            (new SupplierPart())->setId(1)->setPart(
                (new Part())
                    ->setName('part_name_test')
                    ->setNumber('123')
                    ->setBrand((new Brand())->setName('brand_name_test'))
                    ->setFamily((new Family())->setName('family_name_test'))
            )
        );

        $this->command->expects($this->at(0))->method('getItemId')->willReturn($id);
        $this->orderService->expects($this->at(0))
            ->method('loadOrderItem')
            ->with($id)
            ->willReturn($orderItem);

        $this->command->expects($this->at(1))->method('getPrice')->willReturn(300);
        $this->command->expects($this->at(2))->method('getCost')->willReturn(200);
        $this->command->expects($this->at(3))->method('getDelivery')->willReturn(100);
        $this->command->expects($this->at(4))->method('getQty')->willReturn(1);

        $this->orderService->expects($this->at(1))
            ->method('updateItemPrices')
            ->with($this->anything(), $this->equalTo(300), $this->equalTo(200), $this->equalTo(100))
            ->willReturn($orderItem);

        $this->orderService->expects($this->at(2))->method('getStatusWorkflow')->willReturn($this->statusWorkflow);

        $this->orderService->expects($this->at(3))
            ->method('loadSalesCancelReason')
            ->with(CancelReason::ITEM_WAS_REPLACED)
            ->willReturn($reason);

        $this->command->expects($this->at(5))->method('getEditor')->willReturn((new User())->setEmail('test@test.com'));

        $this->statusWorkflow->expects($this->at(0))
            ->method('buildInputItemList')
            ->with([$orderItem])
            ->willReturn($this->inputItemList);

        $event = EventEnum::build(
            EventEnum::TECHNICAL_CANCEL,
            $this->inputItemList,
            [
                TransitionEventInterface::CONTEXT => [
                    'author' => 'test@test.com',
                    'action' => 'Edit Item',
                    'child'  => '',
                ],
                'cancel_reason' => $reason
            ]
        );
        $this->statusWorkflow->expects($this->at(1))
            ->method('raiseTransition')
            ->withAnyParameters()
            ->willReturn($notifyResult);

        $this->orderService->expects($this->at(4))->method('addNoticeAboutCost');
        $this->orderService->expects($this->at(5))->method('save')->with($bundle);
        $this->orderService->expects($this->at(6))->method('triggerNotification')->with($notifyResult);

        ($this->handler)($this->command, $this->orderService, $this->supplierPartService, $this->moneyService);
        $this->assertCount(2, $package->getItems()->toArray());
    }
}
