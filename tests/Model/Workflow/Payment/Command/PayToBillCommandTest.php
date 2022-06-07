<?php

namespace Boodmo\SalesTest\Model\Workflow\Payment\Command;

use Boodmo\Sales\Model\Workflow\Payment\Command\PayToBillCommand;
use PHPUnit\Framework\TestCase;

class PayToBillCommandTest extends TestCase
{
    /**
     * @var PayToBillCommand
     */
    private $command;

    /**
     * @var array
     */
    private $defaultData;

    public function setUp()
    {
        $this->defaultData = [
            'billId'            => '7a6e246b-a347-4435-901b-589c92babf47',
            'paymentInfo'       => ['6a6e246b-a347-4435-901b-589c92babf4c', 12556],
            'creditPointInfo'   => ['9a6e246b-a347-4435-901b-589c92babf4c9', 92556],
            'appliedId'         => '8a6e246b-a347-4435-901b-589c92babf48',
        ];

        $this->command = new PayToBillCommand(
            $this->defaultData['billId'],
            $this->defaultData['paymentInfo'],
            $this->defaultData['creditPointInfo'],
            $this->defaultData['appliedId']
        );
    }

    public function testGetBillId()
    {
        $this->assertEquals($this->defaultData['billId'], $this->command->getBillId());
    }

    public function testGetPaymentInfo()
    {
        $this->assertEquals($this->defaultData['paymentInfo'], $this->command->getPaymentInfo());
    }

    public function testGetCreditPointInfo()
    {
        $this->assertEquals($this->defaultData['creditPointInfo'], $this->command->getCreditPointInfo());
    }

    public function testGetAppliedId()
    {
        $this->assertEquals($this->defaultData['appliedId'], $this->command->getAppliedId());
    }
}
