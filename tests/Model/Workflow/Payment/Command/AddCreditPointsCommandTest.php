<?php

namespace Boodmo\SalesTest\Model\Workflow\Payment\Command;

use Boodmo\Sales\Model\Workflow\Payment\Command\AddCreditPointsCommand;
use PHPUnit\Framework\TestCase;

class AddCreditPointsCommandTest extends TestCase
{
    /**
     * @var AddCreditPointsCommand
     */
    private $command;

    /**
     * @var array
     */
    private $defaultData;

    public function setUp()
    {
        $this->defaultData = [
            'customerId'    => '1',
            'total'         => '10025',
            'currency'      => 'INR',
            'type'          => 'Price decreased by Supplier',
            'id'            => '2',
            'zohobooksId'   => '458850000001724375',
        ];

        $this->command = new AddCreditPointsCommand(
            $this->defaultData['customerId'],
            $this->defaultData['total'],
            $this->defaultData['currency'],
            $this->defaultData['type'],
            $this->defaultData['id'],
            $this->defaultData['zohobooksId']
        );
    }

    public function testGetCustomerId()
    {
        $this->assertEquals($this->defaultData['customerId'], $this->command->getCustomerId());
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

    public function testGetZohobooksId()
    {
        $this->assertEquals($this->defaultData['zohobooksId'], $this->command->getZohobooksId());
    }

    public function testGetBundleId()
    {
        $this->assertEquals($this->defaultData['id'], $this->command->getBundleId());
    }
}
