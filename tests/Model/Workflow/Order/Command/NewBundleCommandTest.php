<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Checkout\ShoppingCart;
use Boodmo\Sales\Model\Workflow\Order\Command\NewBundleCommand;
use Boodmo\User\Entity\User;
use PHPUnit\Framework\TestCase;
use Zend\Stdlib\ArrayObject;

class NewBundleCommandTest extends TestCase
{
    /**
     * @dataProvider commandData
     */
    public function testGetAdditionalInfo($expected, $data)
    {
        $command = new NewBundleCommand(...array_values($data));
        $this->assertEquals($expected['additionalInfo'], $command->getAdditionalInfo());
    }

    /**
     * @dataProvider commandData
     */
    public function testGetUser($expected, $data)
    {
        $command = new NewBundleCommand(...array_values($data));
        $this->assertEquals($expected['user'], $command->getUser());
    }

    /**
     * @dataProvider commandData
     */
    public function testGetCart($expected, $data)
    {
        $command = new NewBundleCommand(...array_values($data));
        $this->assertEquals($expected['cart'], $command->getCart());
    }

    public function commandData()
    {
        return [
            'test1' => [
                'expected' => [
                    'additionalInfo' => [],
                    'user' => (new User())->setId(1),
                    'cart' => (new ShoppingCart([], new ArrayObject()))->setCurrency('INR'),
                ],
                'data' => [
                    'cart' => (new ShoppingCart([], new ArrayObject()))->setCurrency('INR'),
                    'user' => (new User())->setId(1),
                    'additionalInfo' => [],
                ]
            ],
            'test2' => [
                'expected' => [
                    'additionalInfo' => ['test1' => '1'],
                    'user' => (new User())->setId(2),
                    'cart' => (new ShoppingCart([], new ArrayObject()))->setCurrency('USD'),
                ],
                'data' => [
                    'cart' => (new ShoppingCart([], new ArrayObject()))->setCurrency('USD'),
                    'user' => (new User())->setId(2),
                    'additionalInfo' => ['test1' => '1'],
                ]
            ]
        ];
    }
}
