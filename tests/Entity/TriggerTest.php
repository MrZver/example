<?php

namespace Boodmo\SalesTest\Entity;

use Boodmo\Sales\Entity\Trigger as Entity;
use Ramsey\Uuid\Uuid;

class TriggerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Entity
     */
    protected $trigger;

    /**
     * @covers \Boodmo\Sales\Entity\Trigger::__construct
     */
    public function setUp()
    {
        $trigger = new Entity();
        $this->trigger = $trigger;
    }

    /**
     * @covers \Boodmo\Sales\Entity\Trigger::setId
     * @covers \Boodmo\Sales\Entity\Trigger::getId
     */
    public function testSetGetId()
    {
        $id = Uuid::uuid4();
        $this->assertEquals($this->trigger, $this->trigger->setId($id));
        $this->assertEquals($id, $this->trigger->getId());
    }

    /**
     * @covers \Boodmo\Sales\Entity\Trigger::setWorkflow
     * @covers \Boodmo\Sales\Entity\Trigger::getWorkflow
     */
    public function testSetGetWorkflow()
    {
        $this->assertEquals($this->trigger, $this->trigger->setWorkflow("SUPPLIER_ORDER"));
        $this->assertEquals("SUPPLIER_ORDER", $this->trigger->getWorkflow());
    }

    /**
     * @covers \Boodmo\Sales\Entity\Trigger::setFrom
     * @covers \Boodmo\Sales\Entity\Trigger::getFrom
     */
    public function testSetGetFrom()
    {
        $this->assertEquals($this->trigger, $this->trigger->setFrom("processing"));
        $this->assertEquals("processing", $this->trigger->getFrom());
    }

    /**
     * @covers \Boodmo\Sales\Entity\Trigger::setTo
     * @covers \Boodmo\Sales\Entity\Trigger::getTo
     */
    public function testSetGetTo()
    {
        $this->assertEquals($this->trigger, $this->trigger->setTo("processing"));
        $this->assertEquals("processing", $this->trigger->getTo());
    }

    /**
     * @covers \Boodmo\Sales\Entity\Trigger::setEventName
     * @covers \Boodmo\Sales\Entity\Trigger::getEventName
     */
    public function testSetGetEventName()
    {
        $this->assertEquals($this->trigger, $this->trigger->setEventName("[shipping].request_sent-dispatched"));
        $this->assertEquals("[shipping].request_sent-dispatched", $this->trigger->getEventName());
    }

    /**
     * @covers \Boodmo\Sales\Entity\Trigger::setType
     * @covers \Boodmo\Sales\Entity\Trigger::getType
     */
    public function testSetGetType()
    {
        $this->assertEquals($this->trigger, $this->trigger->setType("sms"));
        $this->assertEquals("sms", $this->trigger->getType());
    }

    /**
     * @covers \Boodmo\Sales\Entity\Trigger::setTemplate
     * @covers \Boodmo\Sales\Entity\Trigger::getTemplate
     */
    public function testSetGetTemplate()
    {
        $this->assertEquals($this->trigger, $this->trigger->setTemplate("Customer: OrderPackage Dispatched (SMS)"));
        $this->assertEquals("Customer: OrderPackage Dispatched (SMS)", $this->trigger->getTemplate());
    }

    /**
     * @covers \Boodmo\Sales\Entity\Trigger::setActive
     * @covers \Boodmo\Sales\Entity\Trigger::isActive
     */
    public function testSetIsActive()
    {
        $this->assertEquals($this->trigger, $this->trigger->setActive(false));
        $this->assertEquals(false, $this->trigger->isActive());
    }

    public function testGetArrayCopy()
    {
        $this->assertEquals(
            [
                'id' => $this->trigger->getId(),
                'workflow' => null,
                'from' => null,
                'to' => null,
                'eventName' => null,
                'type' => null,
                'template' => null,
                'active' => true,
                'createdAt' => null,
                'updatedAt' => null,
            ],
            $this->trigger->getArrayCopy()
        );

        $created = new \DateTime('2017-10-14');
        $updated = new \DateTime('2017-10-15');
        $this->trigger
            ->setId('9e68b55c-d002-4047-bb0c-ac45a19cb8ec')
            ->setWorkflow('test_workflow')
            ->setFrom('test_from')
            ->setTo('test_to')
            ->setEventName('test_even_name')
            ->setType('test_type')
            ->setTemplate('test_template')
            ->setActive(false)
            ->setCreatedAt($created)
            ->setUpdatedAt($updated);
        $this->assertEquals(
            [
                'id' => '9e68b55c-d002-4047-bb0c-ac45a19cb8ec',
                'workflow' => 'test_workflow',
                'from' => 'test_from',
                'to' => 'test_to',
                'eventName' => 'test_even_name',
                'type' => 'test_type',
                'template' => 'test_template',
                'active' => false,
                'createdAt' => $created,
                'updatedAt' => $updated,
            ],
            $this->trigger->getArrayCopy()
        );
    }
}
