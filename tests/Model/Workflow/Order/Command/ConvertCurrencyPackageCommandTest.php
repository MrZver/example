<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\Command\ConvertCurrencyPackageCommand;
use Boodmo\User\Entity\User;
use PHPUnit\Framework\TestCase;

class ConvertCurrencyPackageCommandTest extends TestCase
{
    /**
     * @var ConvertCurrencyPackageCommand
     */
    private $command;

    /**
     * @var array
     */
    private $defaultData;

    public function setUp()
    {
        $this->defaultData = [
            'editor' => (new User())->setId(1),
            'toCurrency' => 'USD',
            'packageId' => 2
        ];
        $this->command = new ConvertCurrencyPackageCommand(...array_values($this->defaultData));
    }

    public function testGetEditor()
    {
        $this->assertEquals($this->defaultData['editor'], $this->command->getEditor());
    }

    public function testToCurrency()
    {
        $this->assertEquals($this->defaultData['toCurrency'], $this->command->toCurrency());
    }

    public function testGetPackageId()
    {
        $this->assertEquals($this->defaultData['packageId'], $this->command->getPackageId());
    }
}
