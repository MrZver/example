<?php

namespace Boodmo\SalesTest\Model\Workflow\Payment\Command;

use Boodmo\Sales\Model\Workflow\Payment\Command\EditCreditPointsCommand;
use PHPUnit\Framework\TestCase;

class EditCreditPointsCommandTest extends TestCase
{
    /**
     * @var EditCreditPointsCommand
     */
    private $command;

    /**
     * @var array
     */
    private $defaultData;

    public function setUp()
    {
        $this->defaultData = [
            'creditPointId' => '6a6e246b-a347-4435-901b-589c92babf4c',
            'total'         => '10025',
            'currency'      => 'INR',
            'type'          => 'Price decreased by Supplier',
            'zohobooksId'   => '458850000001724375',
        ];

        $this->command = new EditCreditPointsCommand(
            $this->defaultData['creditPointId'],
            $this->defaultData['total'],
            $this->defaultData['currency'],
            $this->defaultData['type'],
            $this->defaultData['zohobooksId']
        );
    }

    public function testGetCreditPointId()
    {
        $this->assertEquals($this->defaultData['creditPointId'], $this->command->getCreditPointId());
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
}
