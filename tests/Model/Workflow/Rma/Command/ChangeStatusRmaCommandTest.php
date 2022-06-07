<?php

namespace Boodmo\SalesTest\Model\Workflow\Rma\Command;

use Boodmo\Sales\Entity\OrderRma;
use Boodmo\Sales\Model\Workflow\Rma\Command\ChangeStatusRmaCommand;
use PHPUnit\Framework\TestCase;

class ChangeStatusRmaCommandTest extends TestCase
{
    /**
     * @var ChangeStatusRmaCommand
     */
    private $command;

    /**
     * @var array
     */
    private $defaultData;

    public function setUp()
    {
        $this->defaultData = [
            'id'        => '9e68b55c-d002-4047-bb0c-ac45a19cb8ec',
            'status'    => OrderRma::STATUS_REQUESTED,
        ];

        $this->command = new ChangeStatusRmaCommand($this->defaultData['id'], $this->defaultData['status']);
    }

    public function testGetId()
    {
        $this->assertEquals($this->defaultData['id'], $this->command->getId());
    }

    public function testGetStatus()
    {
        $this->assertEquals($this->defaultData['status'], $this->command->getStatus());
    }
}
