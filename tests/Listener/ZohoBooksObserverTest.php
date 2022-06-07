<?php
/**
 * Created by PhpStorm.
 * User: Shandy
 * Date: 03.09.2017
 * Time: 21:27
 */

namespace Boodmo\SalesTest\Listener;

use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Listener\ZohoBooksObserver;
use Boodmo\Sales\Model\Workflow\Status\InputItemList;
use Boodmo\Sales\Model\Workflow\Status\TransitionEvent;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Service\FinanceService;
use PHPUnit\Framework\TestCase;

class ZohoBooksObserverTest extends TestCase
{
    /**
     * @var ZohoBooksObserver
     */
    private $observer;
    /** @var  \PHPUnit_Framework_MockObject_MockObject|FinanceService */
    private $financeService;

    protected function setUp()
    {
        $this->financeService = $this->createMock(FinanceService::class);
        $this->observer = new ZohoBooksObserver($this->financeService);
    }

    public function testCollectListeners()
    {
        $this->observer::collectListeners();
        $result = $this->observer::getDefinitions();
        $this->assertEquals([
            [
                'listener' => get_class($this->observer),
                'method'   => 'dispatched',
                'event'    => 'SHIPMENT_ACCEPT',
                'priority' => 0,
            ],
            [
                'listener' => get_class($this->observer),
                'method'   => 'delivered',
                'event'    => 'SHIPMENT_RECEIVED',
                'priority' => 0,
            ],
            [
                'listener' => get_class($this->observer),
                'method'   => 'rejected',
                'event'    => 'SHIPMENT_REJECT',
                'priority' => 0,
            ],
            [
                'listener' => get_class($this->observer),
                'method'   => 'denied',
                'event'    => 'SHIPMENT_DENY',
                'priority' => 0,
            ],
            [
                'listener' => get_class($this->observer),
                'method'   => 'returnedToSupplier',
                'event'    => 'SHIPMENT_RETURN',
                'priority' => 0,
            ],
        ], $result);
    }

    public function testDispatched()
    {
        [$event, $package] = $this->getExpectEventPackage();
        $this->financeService->expects($this->once())
            ->method('shippingDispatchedObserver')
            ->with($package);
        $this->assertNull($this->observer->dispatched($event));
    }

    public function testDelivered()
    {
        [$event, $package] = $this->getExpectEventPackage();
        $this->financeService->expects($this->once())
            ->method('shippingDeliveredObserver')
            ->with($package);
        $this->assertNull($this->observer->delivered($event));
    }

    public function testRejected()
    {
        [$event, $package] = $this->getExpectEventPackage();
        $this->financeService->expects($this->once())
            ->method('shippingRejectedObserver')
            ->with($package);
        $this->assertNull($this->observer->rejected($event));
    }

    public function testDenied()
    {
        [$event, $package] = $this->getExpectEventPackage();
        $this->financeService->expects($this->once())
            ->method('shippingDeniedObserver')
            ->with($package);
        $this->assertNull($this->observer->denied($event));
    }

    public function testReturnedToSupplier()
    {
        [$event, $package] = $this->getExpectEventPackage();
        $this->financeService->expects($this->once())
            ->method('shippingReturnedToSupplierObserver')
            ->with($package);
        $this->assertNull($this->observer->returnedToSupplier($event));
    }

    protected function getExpectEventPackage()
    {
        $package = $this->createMock(OrderPackage::class);
        $event = $this->createMock(TransitionEventInterface::class);
        $input = $this->createMock(InputItemList::class);
        $item = $this->createMock(OrderItem::class);
        $item->expects($this->once())
            ->method('getPackage')
            ->willReturn($package);
        $input->expects($this->once())
            ->method('toArray')
            ->willReturn([$item]);
        $event->expects($this->once())
            ->method('getTarget')
            ->willReturn($input);

        return [$event, $package];
    }
}
