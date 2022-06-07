<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Command;

use Boodmo\Sales\Entity\OrderRma;
use Boodmo\Sales\Model\Workflow\Order\Command\SetFlagsCommand;
use PHPUnit\Framework\TestCase;

class SetFlagsCommandTest extends TestCase
{
    /**
     * @var SetFlagsCommand
     */
    private $command;

    /**
     * @var array
     */
    private $defaultData;

    public function setUp()
    {
        $this->defaultData = [
            'id'      => '1',
            'entity'  => 'OrderPackage',
            'flag'    => 1,
            'mode'    => true,
        ];

        $this->command = new SetFlagsCommand(
            $this->defaultData['id'],
            $this->defaultData['entity'],
            $this->defaultData['flag'],
            $this->defaultData['mode']
        );
    }

    public function testGetId()
    {
        $this->assertEquals($this->defaultData['id'], $this->command->getId());
    }

    public function testGetStatus()
    {
        $this->assertEquals($this->defaultData['entity'], $this->command->getEntity());
    }

    public function testGetFlag()
    {
        $this->assertEquals($this->defaultData['flag'], $this->command->getFlag());
    }

    public function testGetMode()
    {
        $this->assertEquals($this->defaultData['mode'], $this->command->getMode());
    }
}
