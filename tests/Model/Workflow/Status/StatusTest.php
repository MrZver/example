<?php

namespace Boodmo\SalesTest\Model\Workflow\Status;

use Boodmo\Sales\Model\Workflow\Status\Status;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Model\Workflow\Status\StatusInterface;
use Boodmo\Sales\Model\Workflow\Status\Type;
use PHPUnit\Framework\TestCase;

class StatusTest extends TestCase
{
    /** @var Status */
    private $status;

    /** @var StatusInterface */
    private $statusInterface;

    public function setUp()
    {
        $this->status = Status::fromData(StatusEnum::PROCESSING, ['name' => 'Processing', 'type' => Status::TYPE_GENERAL, 'weight' => 4]);
        $this->statusInterface = $this::createMock(StatusInterface::class);
    }

    /**
     * @covers \Boodmo\Sales\Model\Workflow\Status\Status::getName
     */
    public function testGetName()
    {
        $this->assertEquals('Processing', $this->status->getName());
        $this->assertEquals('', $this->statusInterface->getName());
    }

    /**
     * @covers \Boodmo\Sales\Model\Workflow\Status\Status::getCode
     */
    public function testGetCode()
    {
        $this->assertEquals(StatusEnum::PROCESSING, $this->status->getCode());
        $this->assertEquals(null, $this->statusInterface->getCode());
    }

    /**
     * @covers \Boodmo\Sales\Model\Workflow\Status\Status::getWeight
     */
    public function testGetWeight()
    {
        $this->assertEquals(4, $this->status->getWeight());
        $this->assertEquals(0, $this->statusInterface->getWeight());
    }

    /**
     * @covers \Boodmo\Sales\Model\Workflow\Status\Status::getType
     */
    public function testGetType()
    {
        $this->assertEquals(new Type(Status::TYPE_GENERAL), $this->status->getType());
    }

    /**
     * @covers \Boodmo\Sales\Model\Workflow\Status\Status::toArray
     */
    public function testToArray()
    {
        $this->assertArraySubset([
            'code'   => StatusEnum::PROCESSING,
            'name'   => 'Processing',
            'type'   => ['code' => Status::TYPE_GENERAL, 'name' => 'General'],
            'weight' => 4
        ], $this->status->toArray());
    }

    /**
     * @covers \Boodmo\Sales\Model\Workflow\Status\Status::__toString
     */
    public function testToString()
    {
        $this->assertInternalType('string', $this->status->__toString());
        $this->assertEquals(StatusEnum::PROCESSING, $this->status->__toString());
    }

    /**
     * @covers \Boodmo\Sales\Model\Workflow\Status\Status::fromData()
     */
    public function testFromData()
    {
        $newStatus1 = Status::fromData(StatusEnum::COMPLETE, ['name' => 'Complete', 'weight' => 7, 'type'=> Status::TYPE_GENERAL]);
        $this->assertEquals(new Type(Status::TYPE_GENERAL), $newStatus1->getType());

        $newStatus2 = Status::fromData(StatusEnum::COMPLETE, ['name' => 'Complete', 'weight' => 7, 'type'=> 'P']);
        $this->assertNotEquals(new Type(Status::TYPE_GENERAL), $newStatus2->getType());
    }
}
