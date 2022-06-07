<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\Command\ShipmentRequestSendBoxCommand;
use Boodmo\User\Entity\User;
use PHPUnit\Framework\TestCase;

class ShipmentRequestSendBoxCommandTest extends TestCase
{
    /**
     * @var ShipmentRequestSendBoxCommand
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
            'shipper'       => 'zepo_dhl',
            'trackNum'      => 'S1208210044004'
        ];

        $this->command = new ShipmentRequestSendBoxCommand(
            $this->defaultData['shippingBoxId'],
            $this->defaultData['editor'],
            $this->defaultData['shipper'],
            $this->defaultData['trackNum']
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

    public function testGetShipper()
    {
        $this->assertEquals($this->defaultData['shipper'], $this->command->getShipper());
    }

    public function testGetTrackNum()
    {
        $this->assertEquals($this->defaultData['trackNum'], $this->command->getTrackNum());
    }
}
