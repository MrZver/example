<?php

namespace Boodmo\SalesTest\Model\Workflow;

use Boodmo\Sales\Model\Workflow\Status\Status;
use Boodmo\Sales\Model\Workflow\Status\StatusListInterface;
use Boodmo\SalesTest\Entity\EntityStatusHistoryTraitStub;
use PHPUnit\Framework\TestCase;

class EntityStatusHistoryTraitTest extends TestCase
{
    private $stubTrait;

    protected function setUp()
    {
        $this->stubTrait = new EntityStatusHistoryTraitStub();
    }

    public function testGetStatusHistory()
    {
        $this->assertEmpty($this->stubTrait->getStatusHistory());
    }

    public function testTriggerStatusHistory()
    {
        $diffStatuses = [Status::TYPE_GENERAL => 'test', Status::TYPE_SUPPLIER => 'test1'];
        $diff = $this->createMock(StatusListInterface::class);
        $diff->expects($this->any())
            ->method('toArray')
            ->willReturn($diffStatuses);
        $current = $this->createMock(StatusListInterface::class);
        $current->expects($this->once())
            ->method('diff')
            ->willReturn($diff);
        $next = $this->createMock(StatusListInterface::class);
        $next->expects($this->once())
            ->method('diff')
            ->willReturn($diff);
        $context = ['test' => 'tets'];
        $this->stubTrait->triggerStatusHistory($current, $next, $context);
        $history = $this->stubTrait->getStatusHistory();
        $diffStatuses = array_values($diffStatuses);
        $this->assertCount(1, $history);
        $this->assertSame($context, $history[0]['context']);
        $this->assertSame($diffStatuses, $history[0]['from']);
        $this->assertSame($diffStatuses, $history[0]['to']);
    }

    public function testTriggerStatusHistoryTwice()
    {
        $diffStatuses = [Status::TYPE_GENERAL => 'test', Status::TYPE_SUPPLIER => 'test1'];
        $diff = $this->createMock(StatusListInterface::class);
        $diff->expects($this->any())
            ->method('toArray')
            ->willReturn($diffStatuses);
        $current = $this->createMock(StatusListInterface::class);
        $current->expects($this->any())
            ->method('diff')
            ->willReturn($diff);
        $next = $this->createMock(StatusListInterface::class);
        $next->expects($this->any())
            ->method('diff')
            ->willReturn($diff);
        $diff2 = $this->createMock(StatusListInterface::class);
        $diff2->expects($this->any())
            ->method('toArray')
            ->willReturn([Status::TYPE_GENERAL => 'COMPLETE']);
        $next2 = $this->createMock(StatusListInterface::class);
        $next2->expects($this->any())
            ->method('diff')
            ->willReturn($diff2);
        $this->stubTrait->triggerStatusHistory($current, $next);
        $this->stubTrait->triggerStatusHistory($next, $next2);
        $history = $this->stubTrait->getStatusHistory();
        $this->assertCount(2, $history);
        $this->assertSame(array_values($diffStatuses), $history[0]['from']);
        $this->assertSame(['COMPLETE'], $history[0]['to']);
    }
}
