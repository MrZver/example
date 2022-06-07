<?php
/**
 * Created by PhpStorm.
 * User: Shandy
 * Date: 30.07.2017
 * Time: 18:48
 */

namespace Boodmo\SalesTest\Model;

use Boodmo\Sales\Model\Event\NotifyEvent;
use Boodmo\Sales\Model\NotifyResult;
use PHPUnit\Framework\TestCase;
use Zend\EventManager\EventManagerInterface;

class NotifyResultTest extends TestCase
{
    /**
     * @var NotifyResult
     */
    private $notifyResult;

    public function setUp()
    {
        $this->notifyResult = new NotifyResult();
    }

    public function testConstruct()
    {
        $this->assertCount(0, $this->notifyResult->getEvents());
        $this->notifyResult = new NotifyResult([]);
        $this->assertCount(0, $this->notifyResult->getEvents());
        $event = $this->createMock(NotifyEvent::class);
        $this->notifyResult = new NotifyResult([$event]);
        $this->assertCount(1, $this->notifyResult->getEvents());
        $this->expectException(\TypeError::class);
        $this->notifyResult = new NotifyResult([new \stdClass()]);
    }

    public function testAddEvent()
    {
        $event = $this->createMock(NotifyEvent::class);
        $this->notifyResult->addEvent($event);
        $this->assertCount(1, $this->notifyResult->getEvents());
        $this->expectException(\TypeError::class);
        $this->notifyResult->addEvent(new \stdClass());
    }

    public function testTriggerEvents()
    {
        $event = $this->createMock(NotifyEvent::class);
        $this->notifyResult->addEvent($event);
        $manager = $this->createMock(EventManagerInterface::class);
        $manager->expects($this->any())
            ->method('triggerEvent')
            ->with($event);
        $this->notifyResult->triggerEvents($manager);
        $this->assertCount(0, $this->notifyResult->getEvents());
    }

    public function testMerge()
    {
        $event = $this->createMock(NotifyEvent::class);
        $this->notifyResult->addEvent($event);
        $notifyResult = new NotifyResult([$this->createMock(NotifyEvent::class)]);
        $this->notifyResult->merge($notifyResult);
        $this->assertCount(2, $this->notifyResult->getEvents());
    }
}
