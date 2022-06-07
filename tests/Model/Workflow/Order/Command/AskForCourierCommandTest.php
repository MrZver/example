<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\Command\AskForCourierCommand;
use PHPUnit\Framework\TestCase;

class AskForCourierCommandTest extends TestCase
{
    /**
     * @var AskForCourierCommand
     */
    private $command;

    /**
     * @var array
     */
    private $defaultData;

    public function setUp()
    {
        $this->defaultData = [
            'packageId' => 1,
        ];
        $this->command = new AskForCourierCommand(...array_values($this->defaultData));
    }

    public function testGetPackageId()
    {
        $this->assertEquals($this->defaultData['packageId'], $this->command->getPackageId());
    }
}
