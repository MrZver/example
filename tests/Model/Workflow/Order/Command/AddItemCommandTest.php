<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\Command\AddItemCommand;
use Boodmo\User\Entity\User;
use PHPUnit\Framework\TestCase;

class AddItemCommandTest extends TestCase
{
    /**
     * @var AddItemCommand
     */
    private $command;

    /**
     * @var array
     */
    private $defaultData;

    public function setUp()
    {
        $this->defaultData = [
            'packId' => 1,
            'editor' => new User(),
            'price' => 10025,
            'cost' => 10125,
            'delivery' => 10225,
            'qty' => 2,
            'partId' => 3,
        ];
        $this->command = new AddItemCommand(...array_values($this->defaultData));
    }

    public function testGetPartId()
    {
        $this->assertEquals($this->defaultData['partId'], $this->command->getPartId());
    }
}
