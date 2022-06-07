<?php

namespace Boodmo\SalesTest\Model\Workflow;

use Boodmo\Sales\Model\Workflow\Status\Status;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Model\Workflow\Status\StatusList;
use Boodmo\Sales\Model\Workflow\Status\StatusListInterface;
use Boodmo\Sales\Model\Workflow\Status\StatusProviderAggregateInterface;
use Boodmo\Sales\Model\Workflow\Status\StatusProviderInterface;
use Boodmo\SalesTest\Entity\EntityStatusTraitStub;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class EntityStatusTraitTest extends TestCase
{
    private $stubTrait;
    private $initialStatus = [Status::TYPE_GENERAL => StatusEnum::NULL];

    protected function setUp()
    {
        $this->stubTrait = new EntityStatusTraitStub();
    }

    public function testGetStatus()
    {
        $this->assertEquals($this->initialStatus, $this->stubTrait->getStatus());
    }

    public function testGetStatusList()
    {
        $statusList = $this->stubTrait->getStatusList();
        $this->assertInstanceOf(StatusList::class, $statusList);
        $this->assertEquals($this->initialStatus, $statusList->toArray());
    }

    public function testSetStatusListWithSameList()
    {
        $list = $this->createMock(StatusListInterface::class);
        $list->expects($this->once())
            ->method('toArray')
            ->willReturn($this->initialStatus);

        $this->stubTrait->setStatusList($list);
        $this->assertEquals($this->initialStatus, $this->stubTrait->getStatusList()->toArray());
        $history = $this->stubTrait->getStatusHistory();
        $this->assertCount(0, $history);
    }

    public function testSetStatusListWithParent()
    {
        $diffStatuses = [Status::TYPE_GENERAL => 'PROCESSING'];
        $newList = $this->createMock(StatusListInterface::class);
        $newList->expects($this->any())
            ->method('toArray')
            ->willReturn($diffStatuses);

        $diff = $this->createMock(StatusListInterface::class);
        $diff->expects($this->any())
            ->method('toArray')
            ->willReturn([Status::TYPE_GENERAL => 'COMPLETE']);

        $newList->expects($this->any())
            ->method('diff')
            ->willReturn($diff);

        $firstList = $this->createMock(StatusListInterface::class);
        $firstList->expects($this->once())
            ->method('aggregate')
            ->willReturn($firstList);
        $child = $this->createMock(StatusProviderInterface::class);
        $child->expects($this->any())
            ->method('getStatusList')
            ->willReturn($firstList);
        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())
            ->method('first')
            ->willReturn($child);
        $collection->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$child]));

        $parent = $this->createMock(StatusProviderAggregateInterface::class);
        $parent->expects($this->any())
            ->method('getChildren')
            ->willReturn($collection);

        $this->stubTrait->parent = $parent;

        $this->stubTrait->setStatusList($newList);
        $statusList = $this->stubTrait->getStatusList();
        $this->assertSame($diffStatuses, $statusList->toArray());
    }
}
