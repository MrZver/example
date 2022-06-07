<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\Command\PackedItemsCommand;
use Boodmo\User\Entity\User;
use PHPUnit\Framework\TestCase;

class PackedItemsCommandTest extends TestCase
{
    /**
     * @var PackedItemsCommand
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
            'items'             => [
                ['id' => 1, 'name' => 'test1'],
                ['id' => 2, 'name' => 'test2'],
                ['id' => 3, 'name' => 'test3'],
            ],
            'items_ids'         => [1, 2, 3],
            'shipmentParams'    => [
                'width' => '100.25',
                'height' => '50',
                'length' => '70',
                'weight' => '91,78 kg',
            ],
            'editor'            => $this->user,
            'shippingBoxId'     => 'a83295a1-e402-453f-9484-2b604011bbc8'
        ];

        $this->command = new PackedItemsCommand(
            $this->defaultData['items'],
            $this->defaultData['shipmentParams'],
            $this->defaultData['editor'],
            $this->defaultData['shippingBoxId']
        );
    }

    public function testGetItemsIds()
    {
        $this->assertEquals($this->defaultData['items_ids'], $this->command->getItemsIds());
    }

    public function testGetEditor()
    {
        $this->assertEquals($this->defaultData['editor'], $this->command->getEditor());
    }

    public function testGetShipmentParams()
    {
        $this->assertEquals($this->defaultData['shipmentParams'], $this->command->getShipmentParams());
    }

    public function testGetShippingBoxId()
    {
        $this->assertEquals($this->defaultData['shippingBoxId'], $this->command->getShippingBoxId());
    }
}
