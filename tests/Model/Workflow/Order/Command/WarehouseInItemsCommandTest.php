<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\Command\WarehouseInItemsCommand;
use Boodmo\User\Entity\User;
use PHPUnit\Framework\TestCase;

class WarehouseInItemsCommandTest extends TestCase
{
    /**
     * @dataProvider commandData
     */
    public function testGetItemsIds($expected, $data)
    {
        $command = new WarehouseInItemsCommand(...array_values($data));
        $this->assertEquals($expected['items'], $command->getItemsIds());
    }

    /**
     * @dataProvider commandData
     */
    public function testGetAcceptedList($expected, $data)
    {
        $command = new WarehouseInItemsCommand(...array_values($data));
        $this->assertEquals($expected['accepted'], $command->getAcceptedList());
    }

    /**
     * @dataProvider commandData
     */
    public function testIGetEditor($expected, $data)
    {
        $command = new WarehouseInItemsCommand(...array_values($data));
        $this->assertEquals($expected['editor'], $command->getEditor());
    }

    public function commandData()
    {
        return [
            'test1' => [
                'expected' => [
                    'items' => [1],
                    'accepted' => [1 => 4],
                    'editor' => (new User())->setId(1),
                ],
                'data' => [
                    'items' => [['id' => 1, 'accepted' => 4]],
                    'editor' => (new User())->setId(1),
                ]
            ],
            'test2' => [
                'expected' => [
                    'items' => [1, 2],
                    'accepted' => [1 => 4, 2 => 3],
                    'editor' => (new User())->setId(2),
                ],
                'data' => [
                    'items' => [['id' => 1, 'accepted' => 4], ['id' => 2, 'accepted' => 3]],
                    'editor' => (new User())->setId(2),
                ]
            ]
        ];
    }
}
