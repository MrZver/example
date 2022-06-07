<?php

namespace Boodmo\SalesTest\Model\Workflow\Payment\Command;

use Boodmo\Sales\Model\Workflow\Payment\Command\EditPaymentCommand;
use PHPUnit\Framework\TestCase;

class EditPaymentCommandTest extends TestCase
{
    /**
     * @var EditPaymentCommand
     */
    private $command;

    /**
     * @var array
     */
    private $defaultData;

    public function setUp()
    {
        $this->defaultData = [
            'paymentId'    => '6a6e246b-a347-4435-901b-589c92babf4c',
            'zohobooksId'   => '458850000001724375',
            'transactionId' => 'pay_8N0Jve6XgBDJ22',
            'total'         => '10025',
            'method'        => 'razorpay',
        ];

        $this->command = new EditPaymentCommand(
            $this->defaultData['paymentId'],
            $this->defaultData['zohobooksId'],
            $this->defaultData['transactionId'],
            $this->defaultData['total'],
            $this->defaultData['method']
        );
    }

    public function testGetPaymentId()
    {
        $this->assertEquals($this->defaultData['paymentId'], $this->command->getPaymentId());
    }

    public function testGetTotal()
    {
        $this->assertEquals($this->defaultData['total'], $this->command->getTotal());
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
}
