<?php

namespace Boodmo\SalesTest\Model\Workflow\Payment\Command;

use Boodmo\Sales\Model\Workflow\Payment\Command\AddBillCommand;
use PHPUnit\Framework\TestCase;

class AddBillCommandTest extends TestCase
{
    /**
     * @var AddBillCommand
     */
    private $command;

    /**
     * @var array
     */
    private $defaultData;

    public function setUp()
    {
        $this->defaultData = [
            'bundleId'  => '1',
            'total'     => '10025',
            'currency'  => 'INR',
            'method'    => 'razorpay',
            'type'      => 'prepaid',
        ];

        $this->command = new AddBillCommand(
            $this->defaultData['bundleId'],
            $this->defaultData['total'],
            $this->defaultData['currency'],
            $this->defaultData['method'],
            $this->defaultData['type']
        );
    }

    public function testGetBundleId()
    {
        $this->assertEquals($this->defaultData['bundleId'], $this->command->getBundleId());
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
