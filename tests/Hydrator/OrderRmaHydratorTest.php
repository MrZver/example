<?php

namespace BoodmoApiSales\Test\Hydrator;

use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderRma;
use Boodmo\Sales\Hydrator\OrderRmaHydrator;
use PHPUnit\Framework\TestCase;

class OrderRmaHydratorTest extends TestCase
{
    /**
     * @var OrderRmaHydrator
     */
    protected $hydrator;

    public function setup()
    {
        $this->hydrator = new OrderRmaHydrator();
    }

    public function testExtract()
    {
        $orderItem = (new OrderItem())->setId('0e8e1001-6cb2-40ed-bfb6-a5f2d87ddd21')
            ->setName('item_name')
            ->setBrand('item_brand')
            ->setNumber('item_number');
        $entity = (new OrderRma())
            ->setId('ab01fe33-8d3c-4d86-a217-dfc4df327ecb')
            ->setNumber('2453/2')
            ->setQty(2)
            ->setIntent(OrderRma::INTENTS[OrderRma::MONEY_RETURN]['name'])
            ->setReason(OrderRma::REASONS[OrderRma::BROKEN_PACKAGE]['name'])
            ->setCreatedAt(new \DateTime('2017-11-01'))
            ->setUpdatedAt(new \DateTime('2017-11-02'))
            ->setOrderItem($orderItem);
        $this->assertEquals(
            [
                'id' => 'ab01fe33-8d3c-4d86-a217-dfc4df327ecb',
                'order_item' => [
                    'name'   => 'item_name',
                    'brand'  => 'item_brand',
                    'number' => 'item_number',
                ],
                'number' => '2453/2',
                'qty' => 2,
                'intent' => OrderRma::INTENTS[OrderRma::MONEY_RETURN]['name'],
                'reason' => OrderRma::REASONS[OrderRma::BROKEN_PACKAGE]['name'],
                'status' => OrderRma::STATUS_REQUESTED,
                'created_at' => new \DateTime('2017-11-01'),
                'updated_at' => new \DateTime('2017-11-02'),
                'notes' => [],
            ],
            $this->hydrator->extract($entity)
        );
    }
}
