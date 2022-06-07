<?php

namespace Boodmo\SalesTest\Listener;

use Boodmo\Sales\Listener\BetaoutObserver;
use Boodmo\Sales\Model\Workflow\Status\InputItemList;
use Boodmo\Sales\Model\Workflow\Status\TransitionEvent;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Seo\Service\BetaoutService;
use PHPUnit\Framework\TestCase;

class BetaoutObserverTest extends TestCase
{
    /**
     * @var BetaoutObserver
     */
    protected $observer;


    /**
     * @var BetaoutService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $betaoutService;

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

        $this->betaoutService = $this->createConfiguredMock(BetaoutService::class, ['isBetaoutEnabled' => true]);
        $this->observer = new BetaoutObserver($this->betaoutService);
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
                'priority' => -10,
            ],
            [
                'listener' => get_class($this->observer),
                'method'   => 'clearCart',
                'event'    => 'NEW_ORDER',
                'priority' => -10,
            ],
        ], $result);
    }

    public function testInvoke()
    {
        $this->input->expects($this->once())->method('toArray')->willReturn([]);
        $this->event->expects($this->once())->method('getTarget')->willReturn($this->input);

        $this->betaoutService->expects($this->once())->method('processItemsPurchase');
        $this->assertNull(($this->observer)($this->event));
    }

    public function testClearCart()
    {
        $this->input->expects($this->once())->method('toArray')->willReturn([]);
        $this->event->expects($this->once())->method('getTarget')->willReturn($this->input);

        $this->betaoutService->expects($this->once())->method('processClearCart');
        $this->assertNull($this->observer->clearCart($this->event));
    }
}
