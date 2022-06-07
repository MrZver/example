<?php

namespace Boodmo\SalesTest\Model\Workflow\Bids\Command;

use Boodmo\Sales\Model\Workflow\Bids\Command\MissAskCommand;
use PHPUnit\Framework\TestCase;

class MissAskCommandTest extends TestCase
{
    /**
     * @var MissAskCommand
     */
    private $command;

    /**
     * @var array
     */
    private $defaultData;

    public function setUp()
    {
        $this->defaultData = [
            'itemId' => '9806b405-86cd-47c0-8b61-b9d6965935fd',
            'supplierId' => 2,
        ];
        $this->command = new MissAskCommand(...array_values($this->defaultData));
    }

    public function testGetItemId()
    {
        $this->assertEquals($this->defaultData['itemId'], $this->command->getItemId());
    }

    public function testGetSupplierId()
    {
        $this->assertEquals($this->defaultData['supplierId'], $this->command->getSupplierId());
    }
}
