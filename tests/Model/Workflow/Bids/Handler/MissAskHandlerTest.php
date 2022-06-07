<?php

namespace Boodmo\SalesTest\Model\Workflow\Bids\Handler;

use Boodmo\Sales\Entity\OrderBid;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Model\Workflow\Bids\Command\CancelBidCommand;
use Boodmo\Sales\Model\Workflow\Bids\Command\MissAskCommand;
use Boodmo\Sales\Model\Workflow\Bids\Handler\MissAskHandler;
use Boodmo\Sales\Repository\OrderBidRepository;
use Boodmo\Sales\Service\OrderService;
use Boodmo\User\Entity\UserProfile\Supplier;
use Boodmo\User\Service\SupplierService;
use PHPUnit\Framework\TestCase;

class MissAskHandlerTest extends TestCase
{
    /**
     * @var MissAskHandler
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
     * @var MissAskCommand|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $command;

    public function setUp()
    {
        $this->orderService = $this->createPartialMock(OrderService::class, ['loadOrderItem']);
        $this->orderBidRepository = $this->createPartialMock(OrderBidRepository::class, ['save']);
        $this->supplierService = $this->createPartialMock(SupplierService::class, ['loadSupplierProfile']);
        $this->command = $this->createConfiguredMock(
            MissAskCommand::class,
            ['getItemId' => 2, 'getSupplierId' => '806b0d7d-6448-463b-ab2b-6198352fcbbb']
        );

        $this->handler = new MissAskHandler(
            $this->orderService,
            $this->orderBidRepository,
            $this->supplierService
        );
    }

    public function testInvoke()
    {
        $newBid = new OrderBid();

        $this->supplierService->method('loadSupplierProfile')->willReturn((new Supplier())->setId(2));
        $this->orderService->method('loadOrderItem')->willReturn(
            (new OrderItem())->setId('806b0d7d-6448-463b-ab2b-6198352fcbbb')
        );
        $this->orderBidRepository->method('save')->willReturnCallback(function ($bid) use (&$newBid) {
            $newBid = $bid;
        });

        ($this->handler)($this->command);
        $this->assertEquals(OrderBid::STATUS_MISSED, $newBid->getStatus());
        $this->assertEquals(0, $newBid->getDeliveryDays());
        $this->assertEquals('806b0d7d-6448-463b-ab2b-6198352fcbbb', $newBid->getOrderItem()->getId());
        $this->assertEquals(2, $newBid->getSupplierProfile()->getId());
    }
}
