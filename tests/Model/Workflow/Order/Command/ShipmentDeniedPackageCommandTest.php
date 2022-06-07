<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\Command\ShipmentDeniedPackageCommand;
use Boodmo\User\Entity\User;
use PHPUnit\Framework\TestCase;

class ShipmentDeniedPackageCommandTest extends TestCase
{
    /**
     * @dataProvider commandData
     */
    public function testGetPackageId($expected, $data)
    {
        $command = new ShipmentDeniedPackageCommand(...array_values($data));
        $this->assertEquals($expected['packId'], $command->getPackageId());
    }

    /**
     * @dataProvider commandData
     */
    public function testIGetEditor($expected, $data)
    {
        $command = new ShipmentDeniedPackageCommand(...array_values($data));
        $this->assertEquals($expected['editor'], $command->getEditor());
    }

    public function commandData()
    {
        return [
            'test1' => [
                'expected' => [
                    'packId' => 2,
                    'editor' => (new User())->setId(1),
                ],
                'data' => [
                    'packId' => 2,
                    'editor' => (new User())->setId(1),
                ]
            ],
            'test2' => [
                'expected' => [
                    'packId' => 3,
                    'editor' => (new User())->setId(2),
                ],
                'data' => [
                    'packId' => 3,
                    'editor' => (new User())->setId(2),
                ]
            ]
        ];
    }
}
