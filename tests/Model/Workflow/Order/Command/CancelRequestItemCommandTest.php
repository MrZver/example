<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\Command\CancelRequestItemCommand;
use Boodmo\User\Entity\User;
use PHPUnit\Framework\TestCase;

class CancelRequestItemCommandTest extends TestCase
{
    /**
     * @var CancelRequestItemCommand
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
            'isCustomer' => true,
        ];
        $this->command = new CancelRequestItemCommand(...array_values($this->defaultData));
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

    public function testIsCustomer()
    {
        $this->assertEquals($this->defaultData['isCustomer'], $this->command->isCustomer());
    }
}
