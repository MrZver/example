<?php

namespace Boodmo\SalesTest\Model\Workflow;

use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Model\Event\NotifyEvent;
use Boodmo\Sales\Model\NotifyResult;
use Boodmo\Sales\Model\Workflow\Status\InputItemList;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Model\Workflow\Status\StatusHistoryInterface;
use Boodmo\Sales\Model\Workflow\Status\StatusListInterface;
use Boodmo\Sales\Model\Workflow\Status\StatusProviderInterface;
use Boodmo\Sales\Model\Workflow\Status\TransitionEvent;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Model\Workflow\StatusWorkflow;
use PHPUnit\Framework\TestCase;
use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\ListenerAggregateTrait;

class StatusWorkflowTest extends TestCase
{
    public function testRaiseTransitionNotActive()
    {
        $workflow = new StatusWorkflow();
        $input = $this->createMock(InputItemList::class);
        $input->expects($this->any())
            ->method('getIterator');
        $input->expects($this->any())
            ->method('toArray')
            ->willReturn([$this->createConfiguredMock(OrderItem::class, ['getId' => 1])]);

        $event = $this->createMock(TransitionEventInterface::class);
        $event->expects($this->any())
            ->method('getName')
            ->willReturn('test');
        $event->expects($this->once())
            ->method('isActive')
            ->willReturn(false);
        $event->expects($this->any())
            ->method('getTarget')
            ->willReturn($input);

        $this->expectException(\RuntimeException::class);
        $result = $workflow->raiseTransition($event);
    }

    public function testRaiseTransitionEmpty()
    {
        $workflow = new StatusWorkflow();
        $input = $this->createMock(InputItemList::class);
        $input->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([]));

        $event = $this->createMock(TransitionEventInterface::class);
        $event->expects($this->any())
            ->method('getName')
            ->willReturn('test');
        $event->expects($this->once())
            ->method('isActive')
            ->willReturn(true);
        $event->expects($this->any())
            ->method('getTarget')
            ->willReturn($input);
        $result = $workflow->raiseTransition($event);
        $this->assertInstanceOf(NotifyResult::class, $result);
    }

    public function testAttachListeners()
    {
        $eventManager = new EventManager();
        $listenerAggregate = $this->createMock(ListenerAggregateInterface::class);
        $listenerAggregate->expects($this->once())
            ->method('attach')
            ->with($eventManager);
        $workflow = new StatusWorkflow($listenerAggregate);
        $workflow->setEventManager($eventManager);
    }

    public function testRaiseTransitionWithWrongListener()
    {
        $listenerAggregate = new class implements ListenerAggregateInterface {
            use ListenerAggregateTrait;

            public function attach(EventManagerInterface $events, $priority = 1)
            {
                $events->attach('*', $this, 1000);
            }

            public function __invoke(EventInterface $e)
            {
                $e->stopPropagation(true);
                return 'string';
            }
        };
        $input = $this->createMock(InputItemList::class);
        $input->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([]));
        $workflow = new StatusWorkflow($listenerAggregate);
        $event = new TransitionEvent('test', $input, []);
        $this->expectExceptionMessage('No result after transition (test).');
        $result = $workflow->raiseTransition($event);
        $this->assertInstanceOf(NotifyResult::class, $result);
    }

    public function testRaiseTransition()
    {
        $workflow = new StatusWorkflow();

        $statusProviderEntity = $this->createPartialMock(
            StatusProviderInterface::class,
            ['getStatusList', 'setStatusList', 'getId', 'getParent']
        );
        $statusHistoryEntity = $this->createMock(StatusHistoryInterface::class);
        $input = $this->createMock(InputItemList::class);
        $newStatusList = $this->createMock(StatusListInterface::class);

        $event = $this->createMock(TransitionEventInterface::class);
        // For event manager trigger method need set up event name
        $event->expects($this->at(0))
            ->method('getName')
            ->willReturn('test');
        // Next, $event->getTarget should return InputItemList - $input mock
        $event->expects($this->any())
            ->method('getTarget')
            ->willReturn($input);

        // 1. Firstly, check collectHistory method call
        // foreach by result getSubjectList
        $input->expects($this->at(0))
            ->method('getSubjectList')
            ->willReturn(['test_hash' => $statusHistoryEntity]);
        // In foreach get history in $statusHistoryEntity
        $statusHistoryEntity->expects($this->once())
            ->method('getStatusHistory')
            ->willReturn([]);

        // 2. Well, now call transition method
        // Check isActive event
        $event->expects($this->once())
            ->method('isActive')
            ->willReturn(true);
        // Foreach by getInputRule
        $event->expects($this->any())
            ->method('getInputRule')
            ->willReturn(['PROCESSING' => ItemFilterStub::class]);
        // In cycle another foreach by input list
        $input->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$statusProviderEntity]));
        // For entity get status list
        $statusProviderEntity->expects($this->any())
            ->method('getStatusList')
            ->willReturn($newStatusList);
        // And get id
        $statusProviderEntity->expects($this->any())
            ->method('getId')
            ->willReturn('123');
        $newStatusList->expects($this->once())
            ->method('remove')
            ->with(StatusEnum::build('PROCESSING'))
            ->willReturn($newStatusList);

        // Foreach by output rule
        $event->expects($this->once())
            ->method('getOutputRule')
            ->willReturn(['COMPLETE' => ItemFilterStub::class]);
        $newStatusList->expects($this->once())
            ->method('add')
            ->with(StatusEnum::build('COMPLETE'))
            ->willReturn($newStatusList);
        // Final foreach by input list
        $statusProviderEntity->expects($this->any())
            ->method('setStatusList')
            ->with($newStatusList, []);

        $newStatusHistory = $this->createMock(StatusHistoryInterface::class);
        $input->expects($this->at(4))
            ->method('getSubjectList')
            ->willReturn(['test_hash' => $newStatusHistory]);
        $newStatusHistory->expects($this->any())
            ->method('getStatusHistory')
            ->willReturn([['to' => ['COMPLETE']]]);

        $result = $workflow->raiseTransition($event);
        $this->assertInstanceOf(NotifyResult::class, $result);
        $this->assertCount(1, $result->getEvents());
        $notify = $result->getEvents()[0];
        $this->assertInstanceOf(NotifyEvent::class, $notify);
        $this->assertEquals('*->COMPLETE['.get_class($newStatusHistory).']', $notify->getName());
        $this->assertInstanceOf(get_class($newStatusHistory), $notify->getTarget());
    }
}
