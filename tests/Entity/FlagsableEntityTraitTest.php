<?php

namespace Boodmo\SalesTest\Model;

use Boodmo\SalesTest\Entity\FlagsableEntityTraitStub;
use PHPUnit\Framework\TestCase;

class FlagsableEntityTraitTest extends TestCase
{
    /**
     * @var FlagsableEntityTraitStub
     */
    private $entity;

    public function setUp()
    {
        $this->entity = new FlagsableEntityTraitStub();
    }

    /**
     * @covers \Boodmo\Sales\Entity\FlagsableEntityTrait::getFlags
     * @covers \Boodmo\Sales\Entity\FlagsableEntityTrait::setFlags
     */
    public function testSetGetFlags()
    {
        $this->assertEquals(0, $this->entity->getFlags());

        $this->assertEquals($this->entity, $this->entity->setFlags(1));
        $this->assertEquals(1, $this->entity->getFlags());
    }

    public function testResetAdminValidationFlag()
    {
        //flag & ~1 => flag & -2
        $this->assertEquals(0, $this->entity->resetAdminValidationFlag()->getFlags());              //00 & ~1 => 00
        $this->assertEquals(0, $this->entity->setFlags(0)->resetAdminValidationFlag()->getFlags()); //00 & ~1 => 00
        $this->assertEquals(0, $this->entity->setFlags(1)->resetAdminValidationFlag()->getFlags()); //01 & ~1 => 00
        $this->assertEquals(2, $this->entity->setFlags(2)->resetAdminValidationFlag()->getFlags()); //10 & ~1 => 10
        $this->assertEquals(2, $this->entity->setFlags(3)->resetAdminValidationFlag()->getFlags()); //11 & ~1 => 10
    }

    public function testResetCustomerValidationFlag()
    {
        //flag & ~2 => flag & -3
        $this->assertEquals(0, $this->entity->resetCustomerValidationFlag()->getFlags());              //00 & ~2 => 00
        $this->assertEquals(0, $this->entity->setFlags(0)->resetCustomerValidationFlag()->getFlags()); //00 & ~2 => 00
        $this->assertEquals(1, $this->entity->setFlags(1)->resetCustomerValidationFlag()->getFlags()); //01 & ~2 => 01
        $this->assertEquals(0, $this->entity->setFlags(2)->resetCustomerValidationFlag()->getFlags()); //10 & ~2 => 00
        $this->assertEquals(1, $this->entity->setFlags(3)->resetCustomerValidationFlag()->getFlags()); //11 & ~2 => 01
    }

    public function testResetFlags()
    {
        $this->assertEquals(0, $this->entity->resetFlags()->getFlags());
        $this->assertEquals(0, $this->entity->setFlags(0)->resetFlags()->getFlags());
        $this->assertEquals(0, $this->entity->setFlags(1)->resetFlags()->getFlags());
        $this->assertEquals(0, $this->entity->setFlags(2)->resetFlags()->getFlags());
        $this->assertEquals(0, $this->entity->setFlags(3)->resetFlags()->getFlags());
    }
}
