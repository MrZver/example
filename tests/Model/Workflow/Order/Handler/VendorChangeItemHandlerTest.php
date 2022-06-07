<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Handler;

use Boodmo\Catalog\Service\PartService;
use Boodmo\Catalog\Service\SupplierPartService;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Model\Workflow\Order\Handler\VendorChangeItemHandler;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Model\Workflow\Status\StatusList;
use Boodmo\Sales\Service\OrderService;
use Boodmo\User\Entity\UserProfile\Supplier;
use Boodmo\User\Service\SupplierService;
use PHPUnit\Framework\TestCase;

class VendorChangeItemHandlerTest extends TestCase
{
    /**
     * @var SupplierService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $supplierService;

    /**
     * @var SupplierPartService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $supplierPartService;

    /**
     * @var OrderService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderService;

    /**
     * @var PartService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $partService;

    /**
     * @var VendorChangeItemHandler
     */
    protected $handler;

    /**
     * @var \ReflectionMethod
     */
    protected $getPackageForNewItemMethod;

    /**
     * @var \ReflectionMethod
     */
    protected $getNewOrderItemMethod;

    /**
     * @var \ReflectionMethod
     */
    protected $getTransitionNameMethod;

    /**
     * @var \ReflectionMethod
     */
    protected $prepareBidsMethod;

    public function setUp()
    {
        $this->supplierService = $this->createPartialMock(SupplierService::class, ['getDeliveryDaysByPin']);
        $this->supplierPartService = $this->createMock(SupplierPartService::class);
        $this->orderService = $this->createPartialMock(OrderService::class, ['loadPackage', 'save']);
        $this->partService = $this->createMock(PartService::class);

        $this->supplierService ->method('getDeliveryDaysByPin')->willReturnArgument(0);

        $this->handler = new VendorChangeItemHandler(
            $this->supplierService,
            $this->supplierPartService,
            $this->orderService,
            $this->partService
        );
        $reflector = new \ReflectionObject($this->handler);
        $this->getPackageForNewItemMethod = $reflector->getMethod('getPackageForNewItem');
        $this->getPackageForNewItemMethod->setAccessible(true);
        $this->getNewOrderItemMethod = $reflector->getMethod('getNewOrderItem');
        $this->getNewOrderItemMethod->setAccessible(true);
        $this->getTransitionNameMethod = $reflector->getMethod('getTransitionName');
        $this->getTransitionNameMethod->setAccessible(true);
        $this->prepareBidsMethod = $reflector->getMethod('prepareBids');
        $this->prepareBidsMethod->setAccessible(true);
    }

    public function testGetTransitionName()
    {
        $orderItem = (new OrderItem())->setStatusList(new StatusList([StatusEnum::PROCESSING]));
        $this->assertEquals(
            EventEnum::SPLIT_SUPPLIER,
            $this->getTransitionNameMethod->invoke($this->handler, $orderItem)
        );

        $orderItem = (new OrderItem())->setStatusList(new StatusList([StatusEnum::DROPSHIPPED]));
        $this->assertEquals(
            EventEnum::SPLIT_CANCEL_SUPPLIER,
            $this->getTransitionNameMethod->invoke($this->handler, $orderItem)
        );
    }

    /**
     * @dataProvider getPackageForNewItemData
     */
    public function testGetPackageForNewItem($expected, $data, $preInit = null)
    {
        if ($preInit !== null && is_callable($preInit)) {
            $preInit($this->orderService);
        }
        /* @var OrderPackage $package */
        $package = $this->getPackageForNewItemMethod->invoke(
            $this->handler,
            (new OrderBundle())->setCustomerAddress(['pin' => $data['pin']]),
            $data['package'],
            $data['supplier'],
            $data['packageId'],
            null
        );
        $this->assertEquals($expected['getDeliveryDays'], $package->getDeliveryDays());
        $this->assertEquals($expected['getCurrency'], $package->getCurrency());
        $this->assertEquals($expected['getSupplierProfileId'], $package->getSupplierProfile()->getId());
        if ($data['packageId'] !== null) {
            $this->assertEquals($expected['getId'], $package->getId());
        }
    }

    public function getPackageForNewItemData()
    {
        return [
            'test1' => [
                'expected' => [
                    'getDeliveryDays' => 10,
                    'getCurrency' => 'INR',
                    'getSupplierProfileId' => 1,
                ],
                'data' => [
                    'pin' => 10,
                    'package' => (new OrderPackage())->setCurrency('INR')->setId(100),
                    'supplier' => (new Supplier())->setId(1),
                    'packageId' => null
                ],
                'preInit' => null
            ],
            'test2' => [
                'expected' => [
                    'getDeliveryDays' => 11,
                    'getCurrency' => 'USD',
                    'getSupplierProfileId' => 2,
                    'getId' => 2
                ],
                'data' => [
                    'pin' => 11,
                    'package' => (new OrderPackage())->setCurrency('USD')->setId(100),
                    'supplier' => (new Supplier())->setId(2),
                    'packageId' => 2
                ],
                'preInit' => function ($orderService) {
                    $orderService->method('loadPackage')->willReturnCallback(function ($packageId) {
                        return (new OrderPackage())->setId($packageId)
                            ->setCurrency('USD')
                            ->setDeliveryDays(11)
                            ->setSupplierProfile((new Supplier())->setId(2)->setBaseCurrency('USD'));
                    });
                }
            ]
        ];
    }
}
