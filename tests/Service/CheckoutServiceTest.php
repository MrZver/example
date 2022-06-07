<?php

namespace Boodmo\SalesTest\Service;

use Boodmo\Catalog\Service\PartService;
use Boodmo\Sales\Model\Checkout\InputFilterList;
use Boodmo\Sales\Model\Checkout\Storage\PersistentStorage;
use Boodmo\Sales\Repository\CartRepository;
use Boodmo\Sales\Service\CheckoutService;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Sales\Service\PaymentService;
use Boodmo\Sales\Service\SalesService;
use Boodmo\Shipping\Service\ShippingService;
use Boodmo\User\Entity\User;
use Boodmo\User\Service\SupplierService;
use Boodmo\User\Service\UserService;
use PHPUnit\Framework\TestCase;
use Prooph\ServiceBus\CommandBus;
use Zend\Stdlib\ArrayObject;

class CheckoutServiceTest extends TestCase
{
    /**
     * @var UserService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $userService;
    /**
     * @var SalesService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $salesService;
    /**
     * @var SupplierService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $supplierService;
    /**
     * @var PartService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $partService;
    /**
     * @var OrderService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderService;
    /**
     * @var PaymentService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentService;
    /**
     * @var InputFilterList|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $inputFilterList;
    /**
     * @var CommandBus|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $commandBus;
    /**
     * @var CartRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cartRepository;

    /**
     * @var ShippingService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingService;

    /**
     * @var CheckoutService
     */
    protected $service;

    public function setup()
    {
        $this->userService = $this->createPartialMock(UserService::class, ['getAuthIdentityUser']);
        $this->salesService = $this->createMock(SalesService::class);
        $this->supplierService = $this->createMock(SupplierService::class);
        $this->partService = $this->createMock(PartService::class);
        $this->commandBus = $this->createMock(CommandBus::class);
        $this->orderService = $this->createMock(OrderService::class);
        $this->paymentService = $this->createMock(PaymentService::class);
        $this->inputFilterList = $this->createMock(InputFilterList::class);
        $this->cartRepository = $this->createMock(CartRepository::class);
        $this->shippingService = $this->createMock(ShippingService::class);
        $this->service = new CheckoutService(
            $this->userService,
            $this->salesService,
            $this->supplierService,
            $this->partService,
            $this->commandBus,
            $this->orderService,
            $this->paymentService,
            $this->inputFilterList,
            $this->cartRepository,
            $this->shippingService
        );
    }

    public function testGetPersistStorage()
    {
        $this->userService->method('getAuthIdentityUser')->willReturn((new User())->setId(1));
        $this->assertInstanceOf(PersistentStorage::class, $this->service->getPersistStorage(new ArrayObject()));
    }
}
