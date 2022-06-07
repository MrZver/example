<?php

namespace Boodmo\SalesTest\Entity;

use Boodmo\Sales\Entity\Taxes as Entity;
use Boodmo\Catalog\Entity\Family as FamilyEntity;

class TaxesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Entity
     */
    protected $taxes;

    /**
     * @covers \Boodmo\Sales\Entity\Taxes::__construct
     */
    public function setUp()
    {
        $taxes = new Entity();
        $this->taxes = $taxes;
    }

    /**
     * @covers \Boodmo\Sales\Entity\Taxes::setId
     * @covers \Boodmo\Sales\Entity\Taxes::getId
     */
    public function testSetGetId()
    {
        $this->assertEquals($this->taxes, $this->taxes->setId(1));
        $this->assertEquals(1, $this->taxes->getId());
    }

    /**
     * @covers \Boodmo\Sales\Entity\Taxes::setIgst
     * @covers \Boodmo\Sales\Entity\Taxes::getIgst
     */
    public function testSetGetIgst()
    {
        $this->assertEquals($this->taxes, $this->taxes->setIgst(1));
        $this->assertEquals(1, $this->taxes->getIgst());
    }

    /**
     * @covers \Boodmo\Sales\Entity\Taxes::setSgst
     * @covers \Boodmo\Sales\Entity\Taxes::getSgst
     */
    public function testSetGetSgst()
    {
        $this->assertEquals($this->taxes, $this->taxes->setSgst(1));
        $this->assertEquals(1, $this->taxes->getSgst());
    }

    /**
     * @covers \Boodmo\Sales\Entity\Taxes::setCgst
     * @covers \Boodmo\Sales\Entity\Taxes::getCgst
     */
    public function testSetGetCgst()
    {
        $this->assertEquals($this->taxes, $this->taxes->setCgst(1));
        $this->assertEquals(1, $this->taxes->getCgst());
    }

    /**
     * @covers \Boodmo\Sales\Entity\Taxes::setFamily
     * @covers \Boodmo\Sales\Entity\Taxes::getFamily
     */
    public function testSetGetFamily()
    {
        $family = new FamilyEntity();
        $this->assertEquals($this->taxes, $this->taxes->setFamily($family));
        $this->assertEquals($family, $this->taxes->getFamily());
    }
}
