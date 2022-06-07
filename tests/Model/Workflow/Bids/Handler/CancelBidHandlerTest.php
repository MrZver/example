<?php

namespace Boodmo\SalesTest\Model\Workflow\Bids\Handler;

use Boodmo\Sales\Entity\OrderBid;
use Boodmo\Sales\Model\Workflow\Bids\Command\CancelBidCommand;
use Boodmo\Sales\Model\Workflow\Bids\Handler\CancelBidHandler;
use Boodmo\Sales\Repository\OrderBidRepository;
use PHPUnit\Framework\TestCase;

class CancelBidHandlerTest extends TestCase
{
    /**
     * @var CancelBidHandler
     */
    protected $handler;

    /**
     * @var OrderBidRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderBidRepository;

    /**
     * @var CancelBidCommand|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $command;

    public function setUp()
    {
        $this->orderBidRepository = $this->createPartialMock(OrderBidRepository::class, ['find', 'save']);
        $this->command = $this->createConfiguredMock(CancelBidCommand::class, ['getBidId' => 1]);

        $this->handler = new CancelBidHandler($this->orderBidRepository);
    }

    public function testInvoke()
    {
        $bid = (new OrderBid())->setStatus(OrderBid::STATUS_OPEN);
        $this->orderBidRepository->method('find')->willReturn($bid);

        ($this->handler)($this->command);
        $this->assertEquals(OrderBid::STATUS_CANCELLED, $bid->getStatus());
    }
}
