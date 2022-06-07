<?php

namespace Boodmo\SalesTest\Model\Workflow\Bids\Command;

use Boodmo\Sales\Model\Workflow\Bids\Command\CancelBidCommand;
use PHPUnit\Framework\TestCase;

class CancelBidCommandTest extends TestCase
{
    /**
     * @var CancelBidCommand
     */
    private $command;

    /**
     * @var array
     */
    private $defaultData;

    public function setUp()
    {
        $this->defaultData = [
            'bidId' => '9806b405-86cd-47c0-8b61-b9d6965935fd',
        ];
        $this->command = new CancelBidCommand(...array_values($this->defaultData));
    }

    public function testGetBidId()
    {
        $this->assertEquals($this->defaultData['bidId'], $this->command->getBidId());
    }
}
