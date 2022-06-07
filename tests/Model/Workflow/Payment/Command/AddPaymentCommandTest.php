<?php

namespace Boodmo\SalesTest\Model\Workflow\Payment\Command;

use Boodmo\Sales\Model\Workflow\Payment\Command\AddPaymentCommand;
use PHPUnit\Framework\TestCase;

class AddPaymentCommandTest extends TestCase
{
    /**
     * @var AddPaymentCommand
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
            'method'        => 'razorpay',
            'transactionId' => 'pay_8N0Jve6XgBDJ22',
            'zohobooksId'   => '458850000001724375',
            'customId'      => '6a6e246b-a347-4435-901b-589c92babf4c',
            'cashGateway'   => 'fedex_fedex',
        ];

        $this->command = new AddPaymentCommand(
            $this->defaultData['customerId'],
            $this->defaultData['total'],
            $this->defaultData['currency'],
            $this->defaultData['method'],
            $this->defaultData['transactionId'],
            $this->defaultData['zohobooksId'],
            $this->defaultData['customId'],
            $this->defaultData['cashGateway']
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

    public function testGetMethod()
    {
        $this->assertEquals($this->defaultData['method'], $this->command->getMethod());
    }

    public function testGetTransactionId()
    {
        $this->assertEquals($this->defaultData['transactionId'], $this->command->getTransactionId());
    }

    public function testGetZohobooksId()
    {
        $this->assertEquals($this->defaultData['zohobooksId'], $this->command->getZohobooksId());
    }

    public function testGetCustomId()
    {
        $this->assertEquals($this->defaultData['customId'], $this->command->getCustomId());
    }

    public function testGetCashGateway()
    {
        $this->assertEquals($this->defaultData['cashGateway'], $this->command->getCashGateway());
    }
}
