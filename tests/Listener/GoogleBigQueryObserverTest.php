<?php

namespace Boodmo\SalesTest\Listener;

use Boodmo\Sales\Listener\GoogleBigQueryObserver;
use Boodmo\Sales\Model\Workflow\Status\InputItemList;
use Boodmo\Sales\Model\Workflow\Status\TransitionEvent;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Seo\Service\GuaService;
use PHPUnit\Framework\TestCase;

class GoogleBigQueryObserverTest extends TestCase
{
    /**
     * @var GoogleBigQueryObserver
     */
    protected $observer;


    /**
     * @var GuaService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $guaService;

    /**
     * @var TransitionEvent|TransitionEventInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $event;

    /**
     * @var InputItemList|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $input;

    public function setUp()
    {
        $this->event = $this->createMock(TransitionEventInterface::class);
        $this->input = $this->createMock(InputItemList::class);

        $this->guaService = $this->createMock(GuaService::class);
        $this->observer = new GoogleBigQueryObserver($this->guaService);
    }

    public function testCollectListeners()
    {
        $this->observer::collectListeners();
        $result = $this->observer::getDefinitions();
        $this->assertEquals([
            [
                'listener' => get_class($this->observer),
                'method'   => '__invoke',
                'event'    => 'SHIPMENT_RECEIVED',
                'priority' => 0,
            ],
        ], $result);
    }

    public function testInvoke()
    {
        $this->input->expects($this->once())->method('toArray')->willReturn([]);
        $this->event->expects($this->once())->method('getTarget')->willReturn($this->input);

        $this->guaService->expects($this->once())->method('trackOrder');
        $this->assertNull(($this->observer)($this->event));
    }
}
