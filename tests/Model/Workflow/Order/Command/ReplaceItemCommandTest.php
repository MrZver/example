<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\Command\ReplaceItemCommand;
use Boodmo\User\Entity\User;
use PHPUnit\Framework\TestCase;

class ReplaceItemCommandTest extends TestCase
{
    /**
     * @dataProvider commandData
     */
    public function testGetPartId($expected, $data)
    {
        $command = new ReplaceItemCommand(...array_values($data));
        $this->assertEquals($expected['part_id'], $command->getPartId());
    }

    /**
     * @dataProvider commandData
     */
    public function testIsUpdateDispatch($expected, $data)
    {
        $command = new ReplaceItemCommand(...array_values($data));
        $this->assertEquals($expected['updateDispatch'], $command->isUpdateDispatch());
    }

    public function commandData()
    {
        return [
            'test1' => [
                'expected' => ['part_id' => null, 'updateDispatch' => true],
                'data' => [
                    'item_id' => '92bb1760-2925-4abb-b64f-a803d326dc07',
                    'editor' => (new User())->setId(1),
                    'price' => 15025,
                    'cost' => 14925,
                    'delivery' => 10025,
                    'qty' => 2,
                    'partId' => null
                ]
            ],
            'test2' => [
                'expected' => ['part_id' => 1138, 'updateDispatch' => false],
                'data' => [
                    'item_id' => '92bb1760-2925-4abb-b64f-a803d326dc07',
                    'editor' => (new User())->setId(2),
                    'price' => 15025,
                    'cost' => 14925,
                    'delivery' => 10025,
                    'qty' => 2,
                    'partId' => 1138,
                    'updateDispatch' => false
                ]
            ]
        ];
    }
}
