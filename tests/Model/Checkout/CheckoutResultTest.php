<?php

namespace Boodmo\SalesTest\Model\Checkout;

use Boodmo\Sales\Model\Checkout\CheckoutResult;
use Boodmo\Sales\Model\Checkout\ShoppingCart;
use PHPUnit\Framework\TestCase;

class CheckoutResultTest extends TestCase
{
    /**
     * @var CheckoutResult
     */
    private $checkoutResult;

    /**
     * @var ShoppingCart|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shoppingCart;

    public function setUp()
    {
        $this->shoppingCart = $this->createPartialMock(ShoppingCart::class, ['toArray', 'getStepIndexByName']);
        $this->checkoutResult = new CheckoutResult($this->shoppingCart, true);
    }

    /**
     * @dataProvider addSegmentData
     */
    public function testAddSegment($expected, $key, $value)
    {
        $reflectionClass = new \ReflectionObject($this->checkoutResult);
        $reflectionProperty = $reflectionClass->getProperty('segments');
        $reflectionProperty->setAccessible(true);

        $this->checkoutResult->addSegment($key, $value);

        $this->assertEquals($expected, $reflectionProperty->getValue($this->checkoutResult));
    }

    public function testIsValid()
    {
        $this->assertTrue($this->checkoutResult->isValid());
    }

    /**
     * @dataProvider toArrayData
     */
    public function testToArray($expected, $toArray, $status, $getStepIndexByName, $segments = [])
    {
        $checkoutResult = new CheckoutResult($this->shoppingCart, $status);

        $this->shoppingCart->method('toArray')->willReturn($toArray);
        $this->shoppingCart->method('getStepIndexByName')->willReturn($getStepIndexByName);
        foreach ($segments as $segment) {
            $checkoutResult->addSegment($segment['key'], $segment['value']);
        }

        $this->assertEquals($expected, $checkoutResult->toArray());
    }

    public function testGetData()
    {
        $this->shoppingCart->method('toArray')->willReturn([]);

        $this->assertEquals(
            [
                'success' => true,
                'errors' => [],
                'goto' => null,
                'warning' => null,
                'cart' => $this->shoppingCart
            ],
            $this->checkoutResult->getData()
        );
    }

    public function addSegmentData()
    {
        return [
            'test1' => [
                'expected' => ['' => ''],
                'key' => '',
                'value' => '',
            ],
            'tes2' => [
                'expected' => ['test' => 'test_value'],
                'key' => 'test',
                'value' => 'test_value',
            ],
            'tes3' => [
                'expected' => ['key1' => ['key2' => ['key3' => 'value']]],
                'key' => 'key1/key2/key3',
                'value' => 'value',
            ],
            'tes4' => [
                'expected' => ['key1' => ['key2' => ['key3' => ['value1', 'value2']]]],
                'key' => 'key1/key2/key3',
                'value' => ['value1', 'value2'],
            ],
        ];
    }

    public function toArrayData()
    {
        return [
            'test1' => [
                'expected' => [
                    'success' => false,
                    'errors' => [],
                    'goto' => null,
                    'warning' => null,
                    'cart' => [],
                ],
                'toArray' => [],
                'status' => false,
                'getStepIndexByName' => 1,
                'segments' => [],
            ],
            'test2' => [
                'expected' => [
                    'success' => true,
                    'errors' => [],
                    'goto' => 1,
                    'warning' => null,
                    'cart' => [
                        'product_id1' => ['offer_data'],
                        'product_id2' => ['offer_data'],
                    ],
                    'test_segment1' => 'test_segment_value1',
                    'test_segment2' => 'test_segment_value2',
                ],
                'toArray' => [
                    'product_id1' => ['offer_data'],
                    'product_id2' => ['offer_data'],
                ],
                'status' => true,
                'getStepIndexByName' => 1,
                'segments' => [
                    ['key' => 'test_segment1', 'value' => 'test_segment_value1'],
                    ['key' => 'test_segment2', 'value' => 'test_segment_value2'],
                ],
            ],
        ];
    }
}
