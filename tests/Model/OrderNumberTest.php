<?php

namespace Boodmo\SalesTest\Model;

use Boodmo\Sales\Model\OrderNumber;

class OrderNumberTest extends \PHPUnit\Framework\TestCase
{
    public function testGetNumber()
    {
        $this->assertEquals("1003/800035", OrderNumber::getNumber(\DateTime::createFromFormat('Y-m-d H:i', "2017-03-10 14:58"), 35));
        $this->assertEquals("1003/800035-1", OrderNumber::getNumber(\DateTime::createFromFormat('Y-m-d H:i', "2017-03-10 14:58"), 35, 1));
    }
}
