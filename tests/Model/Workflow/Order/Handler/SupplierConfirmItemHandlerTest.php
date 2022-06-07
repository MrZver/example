<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Handler;

use Boodmo\Sales\Entity\OrderBid;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Model\Workflow\Order\Handler\SupplierConfirmItemHandler;
use Boodmo\Sales\Service\OrderService;
use Boodmo\User\Entity\UserProfile\Supplier;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class SupplierConfirmItemHandlerTest extends TestCase
{
    /**
     * @var SupplierConfirmItemHandler
     */
    protected $handler;

    /**
     * @var OrderService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderService;

    /**
     * @var \ReflectionMethod
     */
    protected $normalizeBidsMethod;

    public function setUp()
    {
        $this->orderService = $this->createMock(OrderService::class);
        $this->handler = new SupplierConfirmItemHandler($this->orderService);

        $reflector = new \ReflectionObject($this->handler);
        $this->normalizeBidsMethod = $reflector->getMethod('normalizeBids');
        $this->normalizeBidsMethod->setAccessible(true);
    }

    public function testNormalizeBids()
    {
        $supplier1 = (new Supplier())->setId(1);
        $supplier2 = (new Supplier())->setId(2);
        $orderItem = (new OrderItem())->setPackage((new OrderPackage())->setSupplierProfile($supplier2));
        $bids = new ArrayCollection();
        $bid1 = (new OrderBid())->setStatus(OrderBid::STATUS_OPEN)->setSupplierProfile($supplier1);
        $bid2 = (new OrderBid())->setStatus(OrderBid::STATUS_ACCEPTED)->setSupplierProfile($supplier1);
        $bid3 = (new OrderBid())->setStatus(OrderBid::STATUS_REJECTED)->setSupplierProfile($supplier1);
        $bid4 = (new OrderBid())->setStatus(OrderBid::STATUS_MISSED)->setSupplierProfile($supplier1);
        $bid5 = (new OrderBid())->setStatus(OrderBid::STATUS_CANCELLED)->setSupplierProfile($supplier1);
        $bid6 = (new OrderBid())->setStatus(OrderBid::STATUS_CANCELLED)->setSupplierProfile($supplier2);
        $bids->add($bid1);
        $bids->add($bid2);
        $bids->add($bid3);
        $bids->add($bid4);
        $bids->add($bid5);
        $bids->add($bid6);
        $orderItem->setBids($bids);
        $this->normalizeBidsMethod->invoke($this->handler, $orderItem);
        $this->assertEquals(OrderBid::STATUS_REJECTED, $bid1->getStatus(), 'bid1: STATUS_OPEN->STATUS_REJECTED');
        $this->assertEquals(OrderBid::STATUS_REJECTED, $bid2->getStatus(), 'bid2: STATUS_ACCEPTED->STATUS_REJECTED');
        $this->assertEquals(OrderBid::STATUS_REJECTED, $bid3->getStatus(), 'bid3: STATUS_REJECTED->STATUS_REJECTED');
        $this->assertEquals(OrderBid::STATUS_MISSED, $bid4->getStatus(), 'bid4: STATUS_MISSED->STATUS_MISSED');
        $this->assertEquals(OrderBid::STATUS_CANCELLED, $bid5->getStatus(), 'bid5: STATUS_CANCELLED->STATUS_CANCELLED');
        $this->assertEquals(
            OrderBid::STATUS_ACCEPTED,
            $bid6->getStatus(),
            'bid6(by supplier): STATUS_CANCELLED->STATUS_ACCEPTED'
        );
    }
}
