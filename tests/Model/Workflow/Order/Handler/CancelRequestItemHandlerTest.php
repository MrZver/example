<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Handler;

use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Model\Workflow\Order\Handler\CancelRequestItemHandler;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\Status;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Service\OrderService;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

/**
 * Class CancelRequestItemHandlerTest
 * @package Boodmo\SalesTest\Model\Workflow\Order\Handler
 * @coversDefaultClass \Boodmo\Sales\Model\Workflow\Order\Handler\CancelRequestItemHandler
 */
class CancelRequestItemHandlerTest extends TestCase
{

    /**
     * @var CancelRequestItemHandler
     */
    private $handler;

    /**
     * @var \ReflectionMethod
     */
    private $isUnpaidItemMethod;

    /**
     * @var \ReflectionMethod
     */
    private $getEventCodeMethod;

    /**
     * @var OrderItem|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderItem;

    /**
     * @var OrderPackage|\PHPUnit_Framework_MockObject_MockObject|OrderPackage
     */
    private $orderPackage;

    /**
     * @var OrderBundle|\PHPUnit_Framework_MockObject_MockObject|OrderBundle
     */
    private $orderBundle;

    /**
     * @var OrderService|\PHPUnit_Framework_MockObject_MockObject|OrderBundle
     */
    private $orderService;

    public function setUp()
    {
        $this->orderService = $this->createMock(OrderService::class);
        $this->handler = new CancelRequestItemHandler($this->orderService);
        $reflector = new \ReflectionObject($this->handler);
        $this->isUnpaidItemMethod = $reflector->getMethod('isUnpaidItem');
        $this->isUnpaidItemMethod->setAccessible(true);
        $this->getEventCodeMethod = $reflector->getMethod('getEventCode');
        $this->getEventCodeMethod->setAccessible(true);

        $itemId = Uuid::uuid4();
        $this->orderItem = $this->createPartialMock(OrderItem::class, ['getPackage', 'getStatus', 'getId']);
        $this->orderPackage = $this->createPartialMock(OrderPackage::class, ['getBundle', 'getCurrency']);
        $this->orderBundle = $this->createPartialMock(OrderBundle::class, ['getPaymentsAppliedMoney']);

        $this->orderItem->method('getPackage')->willReturn($this->orderPackage);
        $this->orderItem->method('getId')->willReturn($itemId);
        $this->orderPackage->method('getBundle')->willReturn($this->orderBundle);
    }

    /**
     * @covers ::getEventCode
     * @covers ::__construct
     * @dataProvider getEventCodeData
     */
    public function testGetEventCode($expected, $expectedExceptionMessage, $isCustomer, $getStatus)
    {

        $this->orderItem->method('getStatus')->willReturn($getStatus);

        if (!empty($expectedExceptionMessage)) {
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        $this->assertEquals(
            $expected,
            $this->getEventCodeMethod->invoke($this->handler, $this->orderItem, $isCustomer)
        );
    }

    /**
     * @covers ::isUnpaidItem
     * @dataProvider isUnpaidItemData
     */
    public function testIsUnpaidItem($expected, $getCurrency, $getPaymentsAppliedMoney)
    {
        $this->orderPackage->method('getCurrency')->willReturn($getCurrency);
        $this->orderBundle->method('getPaymentsAppliedMoney')->willReturn($getPaymentsAppliedMoney);

        $this->assertEquals($expected, $this->isUnpaidItemMethod->invoke($this->handler, $this->orderItem));
    }

    public function isUnpaidItemData()
    {
        return [
            'test1' => [
                'expected' => true,
                'getCurrency' => 'INR',
                'getPaymentsAppliedMoney' => [],
            ],
            'test2' => [
                'expected' => true,
                'getCurrency' => 'INR',
                'getPaymentsAppliedMoney' => ['INR' => new Money(0, new Currency('INR'))],
            ],
            'test3' => [
                'expected' => true,
                'getCurrency' => 'INR',
                'getPaymentsAppliedMoney' => ['USD' => new Money(0, new Currency('USD'))],
            ],
            'test4' => [
                'expected' => true,
                'getCurrency' => 'INR',
                'getPaymentsAppliedMoney' => [
                    'INR' => new Money(0, new Currency('INR')),
                    'USD' => new Money(0, new Currency('USD'))
                ],
            ],
            'test5' => [
                'expected' => true,
                'getCurrency' => 'INR',
                'getPaymentsAppliedMoney' => [
                    'INR' => new Money(0, new Currency('INR')),
                    'USD' => new Money(10015, new Currency('USD'))
                ],
            ],
            'test6' => [
                'expected' => false,
                'getCurrency' => 'INR',
                'getPaymentsAppliedMoney' => [
                    'INR' => new Money(10015, new Currency('INR')),
                    'USD' => new Money(0, new Currency('USD'))
                ],
            ],
            'test7' => [
                'expected' => false,
                'getCurrency' => 'INR',
                'getPaymentsAppliedMoney' => [
                    'INR' => new Money(10015, new Currency('INR')),
                    'USD' => new Money(10015, new Currency('USD'))
                ],
            ],
            'test8' => [
                'expected' => false,
                'getCurrency' => 'USD',
                'getPaymentsAppliedMoney' => [
                    'INR' => new Money(0, new Currency('INR')),
                    'USD' => new Money(10015, new Currency('USD'))
                ],
            ],
            'test9' => [
                'expected' => false,
                'getCurrency' => 'USD',
                'getPaymentsAppliedMoney' => [
                    'INR' => new Money(10015, new Currency('INR')),
                    'USD' => new Money(10015, new Currency('USD'))
                ],
            ],
            'test10' => [
                'expected' => true,
                'getCurrency' => 'USD',
                'getPaymentsAppliedMoney' => [
                    'INR' => new Money(10015, new Currency('INR')),
                ],
            ],
            'test11' => [
                'expected' => true,
                'getCurrency' => 'INR',
                'getPaymentsAppliedMoney' => [
                    'USD' => new Money(10015, new Currency('USD')),
                ],
            ]
        ];
    }

    public function getEventCodeData()
    {
        return [
            'test1' => [
                'expected' => null,
                'expectedExceptionMessage' => 'From current status () can not trigger cancel command',
                'isCustomer' => true,
                'getStatus' => [],
            ],
            'test2' => [
                'expected' => EventEnum::CANCEL_NOT_PAID,
                'expectedExceptionMessage' => '',
                'isCustomer' => true,
                'getStatus' => [Status::TYPE_GENERAL => StatusEnum::PROCESSING],
            ],
            'test3' => [
                'expected' => EventEnum::CANCEL_PROCESSING_USER,
                'expectedExceptionMessage' => '',
                'isCustomer' => false,
                'getStatus' => [Status::TYPE_GENERAL => StatusEnum::PROCESSING],
            ],
            'test4' => [
                'expected' => EventEnum::CANCEL_DROPSHIPPED_USER,
                'expectedExceptionMessage' => '',
                'isCustomer' => true,
                'getStatus' => [
                    Status::TYPE_GENERAL => StatusEnum::DROPSHIPPED,
                    Status::TYPE_SUPPLIER => StatusEnum::SUPPLIER_NEW
                ],
            ],
            'test5' => [
                'expected' => EventEnum::CANCEL_CONFIRMED_USER,
                'expectedExceptionMessage' => '',
                'isCustomer' => true,
                'getStatus' => [
                    Status::TYPE_GENERAL => StatusEnum::DROPSHIPPED,
                    Status::TYPE_SUPPLIER => StatusEnum::CONFIRMED
                ],
            ],
            'test6' => [
                'expected' => EventEnum::CANCEL_SHIPPING_USER,
                'expectedExceptionMessage' => '',
                'isCustomer' => true,
                'getStatus' => [
                    Status::TYPE_GENERAL => StatusEnum::DROPSHIPPED,
                    Status::TYPE_SUPPLIER => StatusEnum::READY_FOR_SHIPPING
                ],
            ],
            'test7' => [
                'expected' => EventEnum::CANCEL_SUPPLIER_USER,
                'expectedExceptionMessage' => '',
                'isCustomer' => true,
                'getStatus' => [Status::TYPE_GENERAL => StatusEnum::CANCEL_REQUESTED_SUPPLIER],
            ],
            'test8' => [
                'expected' => null,
                'expectedExceptionMessage' => 'From current status (CANCEL_REQUESTED_USER) can not trigger cancel command',
                'isCustomer' => true,
                'getStatus' => [Status::TYPE_GENERAL => StatusEnum::CANCEL_REQUESTED_USER],
            ],
            'test9' => [
                'expected' => null,
                'expectedExceptionMessage' => 'Status list is empty.',
                'isCustomer' => true,
                'getStatus' => [Status::TYPE_GENERAL => StatusEnum::NEW_SHIPMENT],
            ],
            'test10' => [
                'expected' => null,
                'expectedExceptionMessage' => 'From current status (COMPLETE) can not trigger cancel command',
                'isCustomer' => true,
                'getStatus' => [Status::TYPE_GENERAL => StatusEnum::COMPLETE],
            ],
            'test11' => [
                'expected' => null,
                'expectedExceptionMessage' => 'Status list is empty.',
                'isCustomer' => true,
                'getStatus' => [Status::TYPE_SUPPLIER => StatusEnum::SUPPLIER_NEW],
            ],
            'test12' => [
                'expected' => EventEnum::CANCEL_DROPSHIPPED_USER,
                'expectedExceptionMessage' => '',
                'isCustomer' => true,
                'getStatus' => [Status::TYPE_GENERAL => '', Status::TYPE_SUPPLIER => StatusEnum::SUPPLIER_NEW],
            ],
            'test13' => [
                'expected' => EventEnum::CANCEL_HUB_USER,
                'expectedExceptionMessage' => '',
                'isCustomer' => true,
                'getStatus' => [
                    Status::TYPE_GENERAL => '',
                    Status::TYPE_SUPPLIER => StatusEnum::READY_FOR_SHIPPING_HUB
                ],
            ]
        ];
    }
}
