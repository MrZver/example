<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\Command\ShipmentRejectBoxCommand;
use Boodmo\User\Entity\User;
use PHPUnit\Framework\TestCase;

class ShipmentRejectBoxCommandTest extends TestCase
{
    /**
     * @var ShipmentRejectBoxCommand
     */
    private $command;

    /**
     * @var User|\PHPUnit_Framework_MockObject_MockObject
     */
    private $user;

    /**
     * @var array
     */
    private $defaultData;

    public function setUp()
    {
        $this->user = $this->createMock(User::class);
        $this->defaultData = [
            'shippingBoxId' => '9806b405-86cd-47c0-8b61-b9d6965935fd',
            'editor'        => $this->user,
            'reason'        => 5,
        ];

        $this->command = new ShipmentRejectBoxCommand(
            $this->defaultData['shippingBoxId'],
            $this->defaultData['editor'],
            $this->defaultData['reason']
        );
    }

    public function testGetShippingBoxId()
    {
        $this->assertEquals($this->defaultData['shippingBoxId'], $this->command->getShippingBoxId());
    }

    public function testGetEditor()
    {
        $this->assertEquals($this->defaultData['editor'], $this->command->getEditor());
    }

    public function testGetReason()
    {
        $this->assertEquals($this->defaultData['reason'], $this->command->getReason());
    }
}
