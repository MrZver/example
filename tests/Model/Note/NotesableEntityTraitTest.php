<?php

namespace Boodmo\SalesTest\Model;

use Boodmo\SalesTest\Entity\NotesableEntityTraitStub;
use PHPUnit\Framework\TestCase;

class NotesableEntityTraitTest extends TestCase
{
    /**
     * @var NotesableEntityTraitStub
     */
    private $entity;

    public function setUp()
    {
        $this->entity = new NotesableEntityTraitStub();
    }

    /**
     * @covers \Boodmo\Sales\Model\Workflow\Note\NotesableEntityTrait::setNotes
     * @covers \Boodmo\Sales\Model\Workflow\Note\NotesableEntityTrait::getNotes
     */
    public function testSetGetNotes()
    {
        $this->assertEquals([], $this->entity->getNotes());

        $this->assertEquals($this->entity, $this->entity->setNotes(['notes1' => 'test1']));
        $this->assertEquals(['notes1' => 'test1'], $this->entity->getNotes());
    }
}
