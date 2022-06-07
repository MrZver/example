<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\Command\EditItemCommand;
use Boodmo\User\Entity\User;
use PHPUnit\Framework\TestCase;

class EditItemCommandTest extends TestCase
{
    /**
     * @dataProvider commandData
     */
    public function testGetItemId($expected, $data)
    {
        $command = new EditItemCommand(...array_values($data));
        $this->assertEquals($expected['itemId'], $command->getItemId());
    }

    /**
     * @dataProvider commandData
     */
    public function testGetEditor($expected, $data)
    {
        $command = new EditItemCommand(...array_values($data));
        $this->assertEquals($expected['editor'], $command->getEditor());
    }

    /**
     * @dataProvider commandData
     */
    public function testGetPrice($expected, $data)
    {
        $command = new EditItemCommand(...array_values($data));
        $this->assertEquals($expected['price'], $command->getPrice());
    }

    /**
     * @dataProvider commandData
     */
    public function testGetCost($expected, $data)
    {
        $command = new EditItemCommand(...array_values($data));
        $this->assertEquals($expected['cost'], $command->getCost());
    }

    /**
     * @dataProvider commandData
     */
    public function testGetDelivery($expected, $data)
    {
        $command = new EditItemCommand(...array_values($data));
        $this->assertEquals($expected['delivery'], $command->getDelivery());
    }

    /**
     * @dataProvider commandData
     */
    public function testGetQty($expected, $data)
    {
        $command = new EditItemCommand(...array_values($data));
        $this->assertEquals($expected['qty'], $command->getQty());
    }

    public function commandData()
    {
        return [
            'test1' => [
                'expected' => [
                    'itemId' => '92bb1760-2925-4abb-b64f-a803d326dc07',
                    'editor' => (new User())->setId(1),
                    'price' => 15025,
                    'cost' => 14925,
                    'delivery' => 10025,
                    'qty' => 2,
                ],
                'data' => [
                    'itemId' => '92bb1760-2925-4abb-b64f-a803d326dc07',
                    'editor' => (new User())->setId(1),
                    'price' => 15025,
                    'cost' => 14925,
                    'delivery' => 10025,
                    'qty' => 2,
                ]
            ],
            'test2' => [
                'expected' => [
                    'itemId' => '92bb1760-2925-4abb-b64f-a803d326dc08',
                    'editor' => (new User())->setId(2),
                    'price' => 15026,
                    'cost' => 14926,
                    'delivery' => 10026,
                    'qty' => 6,
                ],
                'data' => [
                    'itemId' => '92bb1760-2925-4abb-b64f-a803d326dc08',
                    'editor' => (new User())->setId(2),
                    'price' => 15026,
                    'cost' => 14926,
                    'delivery' => 10026,
                    'qty' => 6,
                ]
            ]
        ];
    }
}
