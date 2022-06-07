<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\Command\SupplierConfirmItemCommand;
use Boodmo\User\Entity\User;
use PHPUnit\Framework\TestCase;

class SupplierConfirmItemCommandTest extends TestCase
{
    /**
     * @dataProvider commandData
     */
    public function testGetItemId($expected, $data)
    {
        $command = new SupplierConfirmItemCommand(...array_values($data));
        $this->assertEquals($expected['itemId'], $command->getItemId());
    }

    /**
     * @dataProvider commandData
     */
    public function testIGetEditor($expected, $data)
    {
        $command = new SupplierConfirmItemCommand(...array_values($data));
        $this->assertEquals($expected['editor'], $command->getEditor());
    }

    public function commandData()
    {
        return [
            'test1' => [
                'expected' => [
                    'itemId' => '92bb1760-2925-4abb-b64f-a803d326dc07',
                    'editor' => (new User())->setId(1),
                ],
                'data' => [
                    'itemId' => '92bb1760-2925-4abb-b64f-a803d326dc07',
                    'editor' => (new User())->setId(1),
                ]
            ],
            'test2' => [
                'expected' => [
                    'itemId' => '92bb1760-2925-4abb-b64f-a803d326dc08',
                    'editor' => (new User())->setId(2),
                ],
                'data' => [
                    'itemId' => '92bb1760-2925-4abb-b64f-a803d326dc08',
                    'editor' => (new User())->setId(2),
                ]
            ]
        ];
    }
}
