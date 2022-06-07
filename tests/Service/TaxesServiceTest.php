<?php

namespace Boodmo\SalesTest\Service;

use Boodmo\Catalog\Entity\Family;
use Boodmo\Sales\Entity\Taxes;
use Boodmo\Sales\Repository\TaxesRepository;
use Boodmo\Sales\Service\TaxesService;
use Zend\Db\TableGateway\Exception\RuntimeException;

class TaxesServiceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TaxesRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $taxesRepo;

    /**
     * @var TaxesService
     */
    protected $service;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->taxesRepo = $this->createPartialMock(TaxesRepository::class, ['findByFamilyId']);

        $this->service = new TaxesService(
            $this->taxesRepo
        );
    }

    /**
     * @covers \Boodmo\Sales\Service\TaxesService::__construct
     * @covers \Boodmo\Sales\Service\TaxesService::getTaxesFor
     */
    public function testGetTaxesWithExistFamily()
    {
        $taxes = $this->getMockBuilder(Taxes::class)
            ->getMock();
        $family = $this->getMockBuilder(Family::class)
            ->getMock();
        $family->expects($this->once())
            ->method('getId')
            ->willReturn(12);
        $this->taxesRepo->expects($this->once())
            ->method('findByFamilyId')
            ->will($this->returnValue($taxes));

        $this->assertInstanceOf(Taxes::class, $this->service->getTaxesFor($family));
    }

    /**
     * @covers \Boodmo\Sales\Service\TaxesService::getTaxesFor
     */
    /*public function testGetTaxesWithNoExistFamily()
    {
        $taxes = $this->getMockBuilder(Taxes::class)
            ->getMock();
        $family = $this->getMockBuilder(Family::class)
            ->getMock();
        $family->expects($this->once())
            ->method('getId')
            ->willReturn(12);
        $map = [
            [12, null],
            [null, $taxes],
        ];
        $this->taxesRepo->method('findByFamilyId')
            ->will($this->returnValueMap($map));

        $this->assertInstanceOf(Taxes::class, $this->service->getTaxesFor($family));
    }*/

    /**
     * @covers \Boodmo\Sales\Service\TaxesService::getTaxesFor
     */
    public function testGetTaxesWithNoTaxes()
    {
        $this->expectException(RuntimeException::class);
        $family = $this->getMockBuilder(Family::class)
            ->getMock();
        $family->expects($this->once())
            ->method('getId')
            ->willReturn(12);
        $map = [
            [12, null, null],
            [null, null, null],
        ];
        $this->taxesRepo->method('findByFamilyId')
            ->will($this->returnValueMap($map));

        $this->service->getTaxesFor($family);
    }
}
