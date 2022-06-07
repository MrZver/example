<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\Command\CancelReasonChangeItemCommand;
use PHPUnit\Framework\TestCase;

class CancelReasonChangeItemCommandTest extends TestCase
{
    /**
     * @var CancelReasonChangeItemCommand
     */
    private $command;

    /**
     * @var array
     */
    private $defaultData;

    public function setUp()
    {
        $this->defaultData = [
            'itemId' => '806b0d7d-6448-463b-ab2b-6198352fcbbb',
            'reason' => 1,
            'locked' => true,
        ];
        $this->command = new CancelReasonChangeItemCommand(...array_values($this->defaultData));
    }

    public function testGetItemId()
    {
        $this->assertEquals($this->defaultData['itemId'], $this->command->getItemId());
    }

    public function testGetReason()
    {
        $this->assertEquals($this->defaultData['reason'], $this->command->getReason());
    }

    public function testGetLocked()
    {
        $this->assertEquals($this->defaultData['locked'], $this->command->getLocked());
    }
}
