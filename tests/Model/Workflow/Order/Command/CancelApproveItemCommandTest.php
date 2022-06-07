<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\Command\CancelApproveItemCommand;
use Boodmo\User\Entity\User;
use PHPUnit\Framework\TestCase;

class CancelApproveItemCommandTest extends TestCase
{
    /**
     * @var CancelApproveItemCommand
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
            'editor' => (new User())->setId(1),
            'reason' => 1,
            'locked' => true,
        ];
        $this->command = new CancelApproveItemCommand(...array_values($this->defaultData));
    }

    public function testGetItemId()
    {
        $this->assertEquals($this->defaultData['itemId'], $this->command->getItemId());
    }

    public function testGetEditor()
    {
        $this->assertEquals($this->defaultData['editor'], $this->command->getEditor());
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
