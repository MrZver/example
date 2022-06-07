<?php

namespace Boodmo\SalesTest\Model\Workflow\Payment\Command;

use Boodmo\Sales\Model\Workflow\Payment\Command\NewCreditMemoCommand;
use PHPUnit\Framework\TestCase;

class NewCreditMemoCommandTest extends TestCase
{
    /**
     * @var NewCreditMemoCommand
     */
    private $command;

    /**
     * @var array
     */
    private $defaultData;

    public function setUp()
    {
        $this->defaultData = [
            'bundleId' => 1,
            'total' => 10025,
            'calculatedTotal' => 10125,
            'currency' => 'INR',
            'open' => true,
        ];
        $this->command = new NewCreditMemoCommand(...array_values($this->defaultData));
    }

    public function testGetBundleId()
    {
        $this->assertEquals($this->defaultData['bundleId'], $this->command->getBundleId());
    }

    public function testGetTotal()
    {
        $this->assertEquals($this->defaultData['total'], $this->command->getTotal());
    }

    public function testGetCalculatedTotal()
    {
        $this->assertEquals($this->defaultData['calculatedTotal'], $this->command->getCalculatedTotal());
    }

    public function testGetCurrency()
    {
        $this->assertEquals($this->defaultData['currency'], $this->command->getCurrency());
    }

    public function testGetOpen()
    {
        $this->assertEquals($this->defaultData['open'], $this->command->getOpen());
    }
}
