<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\Command\ApproveSupplierItemCommand;
use Boodmo\User\Entity\User;
use PHPUnit\Framework\TestCase;

class ApproveSupplierItemCommandTest extends TestCase
{
    /**
     * @var ApproveSupplierItemCommand
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
            'itemId' => '9806b405-86cd-47c0-8b61-b9d6965935fd',
            'editor' => $this->user,
        ];

        $this->command = new ApproveSupplierItemCommand(
            $this->defaultData['itemId'],
            $this->defaultData['editor']
        );
    }

    public function testGetItemId()
    {
        $this->assertEquals($this->defaultData['itemId'], $this->command->getItemId());
    }

    public function testGetEditor()
    {
        $this->assertEquals($this->defaultData['editor'], $this->command->getEditor());
    }
}
