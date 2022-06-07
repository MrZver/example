<?php

namespace BoodmoApiSales\Test\Hydrator;

use Boodmo\Currency\Service\CurrencyService;
use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Hydrator\OrderItemHydrator;
use Boodmo\Sales\Model\Workflow\Status\Status;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Model\Workflow\Status\StatusList;
use Doctrine\Common\Collections\ArrayCollection;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class OrderItemHydratorTest extends TestCase
{
    /**
     * @var OrderItemHydrator
     */
    private $hydrator;

    /**
     * @var MoneyService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $moneyService;

    /**
     * @var OrderPackage|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderPackage;

    /**
     * @var OrderItem|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderItem;

    /**
     * @var StatusList
     */
    private $statusList;

    public function setUp()
    {
        $this->moneyService = $this->getMockBuilder(MoneyService::class)
            ->setConstructorArgs([$this->createConfiguredMock(CurrencyService::class, ['getCurrencyRate' => 65.00])])
            ->setMethods(['getMoney'])
            ->getMock();
        $this->moneyService->method('getMoney')->will($this->returnCallback(function ($price, $currency) {
            return new Money($price * 100, new Currency($currency));
        }));
        $this->orderPackage = $this->createConfiguredMock(
            OrderPackage::class,
            [
                'getBundle' => $this->createConfiguredMock(OrderBundle::class, [])
            ]
        );
        $this->statusList = (new StatusList())->add(
            Status::fromData(
                StatusEnum::PROCESSING,
                ['name' => 'Processing', 'type' => Status::TYPE_GENERAL, 'weight' => 4]
            )
        );
        $this->orderItem = $this->createConfiguredMock(
            OrderItem::class,
            [
                'getStatusList' => $this->statusList,
                'getPackage' => $this->orderPackage,
                'getParent' => $this->orderPackage,
                'getRmaList' => new ArrayCollection(),
                'getBids' => new ArrayCollection(),
            ]
        );

        $this->hydrator = new OrderItemHydrator($this->moneyService);
    }

    /**
     * @dataProvider extractData
     */
    public function testExtract($expected, $currency, $preInit)
    {
        $id = (string)Uuid::uuid4();
        $expected['id'] = $id;
        $expected['package'] = $this->orderPackage;
        $expected['parent'] = $this->orderPackage;
        $expected['status_list'] = $this->statusList;

        $this->orderItem->method('getId')->willReturn($id);
        if ($preInit and is_callable($preInit)) {
            $preInit($this->orderItem, $this->orderPackage);
        }

        $this->assertEquals($expected, $this->hydrator->extract($this->orderItem, $currency));
    }

    public function extractData()
    {
        return [
            'test1' => [
                'expected' => [
                    'product_id' => 0,
                    'part_id' => 0,
                    'name' => null,
                    'qty' => 0,
                    'price' => new Money(0, new Currency('INR')),
                    'delivery_price' => new Money(0, new Currency('INR')),
                    'sub_total' => new Money(0, new Currency('INR')),
                    'delivery_total' => new Money(0, new Currency('INR')),
                    'base_sub_total' => new Money(0, new Currency('INR')),
                    'brand' => '',
                    'number' => '',
                    'is_cancelled' => false,
                    'origin_price' => new Money(0, new Currency('INR')),
                    'cost' => new Money(0, new Currency('INR')),
                    'dispatch_date' => null,
                    'cancel_reason' => null,
                    'locked' => false,
                    'discount' => 0,
                    'base_price' => new Money(0, new Currency('INR')),
                    'base_cost' => new Money(0, new Currency('INR')),
                    'base_origin_price' => new Money(0, new Currency('INR')),
                    'base_delivery_price' => new Money(0, new Currency('INR')),
                    'base_discount' => 0,
                    'family' => null,
                    'created_at' => null,
                    'updated_at' => null,
                    'status' => [],
                    'status_history' => [],
                    'base_delivery_total' => null,
                    'customer_status' => 'Processing',
                    'notes' => [],
                    'cost_total' => new Money(0, new Currency('INR')),
                    'grand_total' => new Money(0, new Currency('INR')),
                    'rma_list' => [],
                    'rma_total_qty' => 0,
                    'flags' => 0,
                    'bids' => new ArrayCollection(),
                    'confirmation_date' => null,
                    'item_accepted_bid' => null,
                    'is_any_cancelled' => false,
                    'is_replaced' => false,
                ],
                'currency' => null,
                'preInit' => function (OrderItem $orderItem, OrderPackage $orderPackage) {
                    $orderPackage->method('getCurrency')->willReturn('INR');
                }
            ],
            'test2' => [
                'expected' => [
                    'product_id' => 0,
                    'part_id' => 0,
                    'name' => null,
                    'qty' => 1,
                    'price' => new Money(10025, new Currency('INR')),
                    'base_price' => new Money(10025, new Currency('INR')),
                    'delivery_price' => new Money(12500, new Currency('INR')),
                    'base_delivery_price' => new Money(12500, new Currency('INR')),
                    'cost' => new Money(10030, new Currency('INR')),
                    'base_cost' => new Money(10030, new Currency('INR')),
                    'origin_price' => new Money(10035, new Currency('INR')),
                    'base_origin_price' => new Money(10035, new Currency('INR')),
                    'sub_total' => new Money(11035, new Currency('INR')),
                    'base_sub_total' => new Money(11035, new Currency('INR')),
                    'delivery_total' => new Money(12500, new Currency('INR')),
                    'brand' => '',
                    'number' => '',
                    'is_cancelled' => false,
                    'dispatch_date' => null,
                    'cancel_reason' => null,
                    'locked' => false,
                    'discount' => 0,
                    'base_discount' => 0,
                    'family' => null,
                    'created_at' => null,
                    'updated_at' => null,
                    'status' => [],
                    'status_history' => [],
                    'base_delivery_total' => null,
                    'customer_status' => 'Processing',
                    'notes' => [],
                    'cost_total' => new Money(0, new Currency('INR')),
                    'grand_total' => new Money(0, new Currency('INR')),
                    'rma_list' => [],
                    'rma_total_qty' => 0,
                    'flags' => 0,
                    'bids' => new ArrayCollection(),
                    'confirmation_date' => null,
                    'item_accepted_bid' => null,
                    'is_any_cancelled' => false,
                    'is_replaced' => false,
                ],
                'currency' => null,
                'preInit' => function (OrderItem $orderItem, OrderPackage $orderPackage) {
                    $orderPackage->method('getCurrency')->willReturn('INR');
                    $orderItem->method('getQty')->willReturn(1);
                    $orderItem->method('getPrice')->willReturn(10025);
                    $orderItem->method('getBasePrice')->willReturn(10025);
                    $orderItem->method('getDeliveryPrice')->willReturn(12500);
                    $orderItem->method('getBaseDeliveryPrice')->willReturn(12500);
                    $orderItem->method('getCost')->willReturn(10030);
                    $orderItem->method('getBaseCost')->willReturn(10030);
                    $orderItem->method('getOriginPrice')->willReturn(10035);
                    $orderItem->method('getBaseOriginPrice')->willReturn(10035);
                    $orderItem->method('getSubTotal')->willReturn(11035);
                    $orderItem->method('getBaseSubTotal')->willReturn(11035);
                    $orderItem->method('getDeliveryTotal')->willReturn(12500);
                }
            ],
            'test3' => [
                'expected' => [
                    'product_id' => 0,
                    'part_id' => 0,
                    'name' => null,
                    'qty' => 1,
                    'price' => new Money(725, new Currency('USD')),
                    'base_price' => new Money(47100, new Currency('INR')),
                    'delivery_price' => new Money(125, new Currency('USD')),
                    'base_delivery_price' => new Money(8100, new Currency('INR')),
                    'cost' => new Money(825, new Currency('USD')),
                    'base_cost' => new Money(53600, new Currency('INR')),
                    'origin_price' => new Money(925, new Currency('USD')),
                    'base_origin_price' => new Money(60100, new Currency('INR')),
                    'sub_total' => new Money(725, new Currency('USD')),
                    'base_sub_total' => new Money(47100, new Currency('INR')),
                    'delivery_total' => new Money(125, new Currency('USD')),
                    'brand' => '',
                    'number' => '',
                    'is_cancelled' => false,
                    'dispatch_date' => null,
                    'cancel_reason' => null,
                    'locked' => false,
                    'discount' => 0,
                    'base_discount' => 0,
                    'family' => null,
                    'created_at' => null,
                    'updated_at' => null,
                    'status' => [],
                    'status_history' => [],
                    'base_delivery_total' => null,
                    'customer_status' => 'Processing',
                    'notes' => [],
                    'cost_total' => new Money(0, new Currency('USD')),
                    'grand_total' => new Money(0, new Currency('USD')),
                    'rma_list' => [],
                    'rma_total_qty' => 0,
                    'flags' => 0,
                    'bids' => new ArrayCollection(),
                    'confirmation_date' => null,
                    'item_accepted_bid' => null,
                    'is_any_cancelled' => false,
                    'is_replaced' => false,
                ],
                'currency' => null,
                'preInit' => function (OrderItem $orderItem, OrderPackage $orderPackage) {
                    $orderPackage->method('getCurrency')->willReturn('USD');
                    $orderItem->method('getQty')->willReturn(1);
                    $orderItem->method('getPrice')->willReturn(725);
                    $orderItem->method('getBasePrice')->willReturn(47100);
                    $orderItem->method('getDeliveryPrice')->willReturn(125);
                    $orderItem->method('getBaseDeliveryPrice')->willReturn(8100);
                    $orderItem->method('getCost')->willReturn(825);
                    $orderItem->method('getBaseCost')->willReturn(53600);
                    $orderItem->method('getOriginPrice')->willReturn(925);
                    $orderItem->method('getBaseOriginPrice')->willReturn(60100);
                    $orderItem->method('getSubTotal')->willReturn(725);
                    $orderItem->method('getBaseSubTotal')->willReturn(47100);
                    $orderItem->method('getDeliveryTotal')->willReturn(125);
                }
            ],
            'test4' => [
                'expected' => [
                    'product_id' => 0,
                    'part_id' => 0,
                    'name' => null,
                    'qty' => 1,
                    'price' => new Money(47200, new Currency('INR')),//47125
                    'base_price' => new Money(47100, new Currency('INR')),
                    'delivery_price' => new Money(8200, new Currency('INR')),//8125
                    'base_delivery_price' => new Money(8100, new Currency('INR')),
                    'cost' => new Money(53700, new Currency('INR')),//53625
                    'base_cost' => new Money(53600, new Currency('INR')),
                    'origin_price' => new Money(60200, new Currency('INR')),//60125
                    'base_origin_price' => new Money(60100, new Currency('INR')),
                    'sub_total' => new Money(47200, new Currency('INR')),//47125
                    'base_sub_total' => new Money(47100, new Currency('INR')),
                    'delivery_total' => new Money(8200, new Currency('INR')),//8125
                    'brand' => '',
                    'number' => '',
                    'is_cancelled' => false,
                    'dispatch_date' => null,
                    'cancel_reason' => null,
                    'locked' => false,
                    'discount' => 0,
                    'base_discount' => 0,
                    'family' => null,
                    'created_at' => null,
                    'updated_at' => null,
                    'status' => [],
                    'status_history' => [],
                    'base_delivery_total' => null,
                    'customer_status' => 'Processing',
                    'notes' => [],
                    'cost_total' => new Money(0, new Currency('INR')),
                    'grand_total' => new Money(0, new Currency('INR')),
                    'rma_list' => [],
                    'rma_total_qty' => 0,
                    'flags' => 0,
                    'bids' => new ArrayCollection(),
                    'confirmation_date' => null,
                    'item_accepted_bid' => null,
                    'is_any_cancelled' => false,
                    'is_replaced' => false,
                ],
                'currency' => 'INR',
                'preInit' => function (OrderItem $orderItem, OrderPackage $orderPackage) {
                    $orderPackage->method('getCurrency')->willReturn('USD');
                    $orderItem->method('getQty')->willReturn(1);
                    $orderItem->method('getPrice')->willReturn(725);
                    $orderItem->method('getBasePrice')->willReturn(47100);
                    $orderItem->method('getDeliveryPrice')->willReturn(125);
                    $orderItem->method('getBaseDeliveryPrice')->willReturn(8100);
                    $orderItem->method('getCost')->willReturn(825);
                    $orderItem->method('getBaseCost')->willReturn(53600);
                    $orderItem->method('getOriginPrice')->willReturn(925);
                    $orderItem->method('getBaseOriginPrice')->willReturn(60100);
                    $orderItem->method('getSubTotal')->willReturn(725);
                    $orderItem->method('getBaseSubTotal')->willReturn(47100);
                    $orderItem->method('getDeliveryTotal')->willReturn(125);
                }
            ],
            'test5' => [
                'expected' => [
                    'product_id' => 0,
                    'part_id' => 0,
                    'name' => null,
                    'qty' => 1,
                    'price' => new Money(725, new Currency('USD')),
                    'base_price' => new Money(47100, new Currency('INR')),
                    'delivery_price' => new Money(125, new Currency('USD')),
                    'base_delivery_price' => new Money(8100, new Currency('INR')),
                    'cost' => new Money(825, new Currency('USD')),
                    'base_cost' => new Money(53600, new Currency('INR')),
                    'origin_price' => new Money(925, new Currency('USD')),
                    'base_origin_price' => new Money(60100, new Currency('INR')),
                    'sub_total' => new Money(725, new Currency('USD')),
                    'base_sub_total' => new Money(47100, new Currency('INR')),
                    'delivery_total' => new Money(125, new Currency('USD')),
                    'brand' => '',
                    'number' => '',
                    'is_cancelled' => false,
                    'dispatch_date' => null,
                    'cancel_reason' => null,
                    'locked' => false,
                    'discount' => 0,
                    'base_discount' => 0,
                    'family' => null,
                    'created_at' => null,
                    'updated_at' => null,
                    'status' => [],
                    'status_history' => [],
                    'base_delivery_total' => null,
                    'customer_status' => 'Processing',
                    'notes' => [],
                    'cost_total' => new Money(0, new Currency('USD')),
                    'grand_total' => new Money(0, new Currency('USD')),
                    'rma_list' => [],
                    'rma_total_qty' => 0,
                    'flags' => 0,
                    'bids' => new ArrayCollection(),
                    'confirmation_date' => null,
                    'item_accepted_bid' => null,
                    'is_any_cancelled' => false,
                    'is_replaced' => false,
                ],
                'currency' => 'USD',
                'preInit' => function (OrderItem $orderItem, OrderPackage $orderPackage) {
                    $orderPackage->method('getCurrency')->willReturn('INR');
                    $orderItem->method('getQty')->willReturn(1);
                    $orderItem->method('getPrice')->willReturn(47100);
                    $orderItem->method('getBasePrice')->willReturn(47100);
                    $orderItem->method('getDeliveryPrice')->willReturn(8100);
                    $orderItem->method('getBaseDeliveryPrice')->willReturn(8100);
                    $orderItem->method('getCost')->willReturn(53600);
                    $orderItem->method('getBaseCost')->willReturn(53600);
                    $orderItem->method('getOriginPrice')->willReturn(60100);
                    $orderItem->method('getBaseOriginPrice')->willReturn(60100);
                    $orderItem->method('getSubTotal')->willReturn(47100);
                    $orderItem->method('getBaseSubTotal')->willReturn(47100);
                    $orderItem->method('getDeliveryTotal')->willReturn(8100);
                }
            ],
        ];
    }
}
