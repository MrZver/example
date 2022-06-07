<?php

namespace Boodmo\SalesTest\Model\Workflow\Payment\Command;

use Boodmo\Sales\Model\Workflow\Payment\Command\EditBillCommand;
use PHPUnit\Framework\TestCase;

class EditBillCommandTest extends TestCase
{
    /**
     * @var EditBillCommand
     */
    private $command;

    /**
     * @var array
     */
    private $defaultData;

    public function setUp()
    {
        $this->defaultData = [
            'billId'    => '6a6e246b-a347-4435-901b-589c92babf4c',
            'total'     => '10025',
            'currency'  => 'INR',
            'method'    => 'razorpay',
            'type'      => 'prepaid',
        ];

        $this->command = new EditBillCommand(
            $this->defaultData['billId'],
            $this->defaultData['total'],
            $this->defaultData['currency'],
            $this->defaultData['method'],
            $this->defaultData['type']
        );
    }

    public function testGetBillId()
    {
        $this->assertEquals($this->defaultData['billId'], $this->command->getBillId());
    }

    public function testGetTotal()
    {
        $this->assertEquals($this->defaultData['total'], $this->command->getTotal());
    }

    public function testGetCurrency()
    {
        $this->assertEquals($this->defaultData['currency'], $this->command->getCurrency());
    }

    public function testGetType()
    {
        $this->assertEquals($this->defaultData['type'], $this->command->getType());
    }

    public function testGetMethod()
    {
        $this->assertEquals($this->defaultData['method'], $this->command->getMethod());
    }
}
