<?php

namespace Boodmo\SalesTest\Model\Checkout;

use Boodmo\Sales\Model\Checkout\InputFilterList;
use PHPUnit\Framework\TestCase;
use Zend\InputFilter\Factory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterInterface;

class InputFilterListTest extends TestCase
{
    /**
     * @var Factory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $factory;

    /**
     * @var InputFilter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $inputFilter;

    /**
     * @var InputFilterList
     */
    private $inputFilterList;

    public function setUp()
    {
        $this->factory = $this->createPartialMock(Factory::class, ['createInputFilter']);
        $this->inputFilter = $this->createMock(InputFilter::class);
        $this->inputFilterList = new InputFilterList();
    }

    public function testGetForPin()
    {
        $data = [
            'pin' => [
                'required'   => true,
                'filters'    => [
                    ['name' => 'StringTrim'],
                ],
                'validators' => [
                    [
                        'name'                   => 'StringLength',
                        'options'                => ['min' => 6, 'max' => 6],
                        'break_chain_on_failure' => true,
                    ],
                    ['name' => 'Digits'],
                ],
            ]
        ];
        $this->inputFilterList->setFactory($this->factory);
        $this->factory->expects($this->once())->method('createInputFilter')->with($data)
            ->willReturn($this->inputFilter);

        $this->assertInstanceOf(InputFilterInterface::class, $this->inputFilterList->getForPin());
    }

    public function testGetForEmailStep()
    {
        $data = [
            'email' => [
                'required'    => true,
                'filters'     => [
                    [
                        'name'    => 'Zend\Filter\StringTrim',
                        'options' => [],
                    ],
                    [
                        'name'    => 'Zend\Filter\StringToLower',
                        'options' => [
                            'encoding' => 'UTF-8',
                        ],
                    ],
                ],
                'validators'  => [
                    [
                        'name'    => 'EmailAddress',
                        'options' => [],
                    ],
                ],
                'allow_empty' => false,
            ]
        ];
        $this->inputFilterList->setFactory($this->factory);
        $this->factory->expects($this->once())->method('createInputFilter')->with($data)
            ->willReturn($this->inputFilter);

        $this->assertInstanceOf(InputFilterInterface::class, $this->inputFilterList->getForEmailStep());
    }

    public function testGetForAddressStep()
    {
        $data = [
            'first_name' => [
                'required'   => true,
                'filters'    => [
                    ['name' => 'StringTrim'],
                    ['name' => 'PregReplace', 'options' => ['pattern' => '/\s/']],
                ],
                'validators' => [
                    [
                        'name'                   => 'StringLength',
                        'options'                => ['min' => 3],
                        'break_chain_on_failure' => true,
                    ],
                    ['name' => 'Regex', 'options' => ['pattern' => '/^[a-zA-Z]+$/']],
                ],
            ],
            'last_name' => [
                'required'   => true,
                'filters'    => [
                    ['name' => 'StringTrim'],
                    ['name' => 'PregReplace', 'options' => ['pattern' => '/\s/']],
                ],
                'validators' => [
                    [
                        'name'                   => 'StringLength',
                        'options'                => ['min' => 3],
                        'break_chain_on_failure' => true,
                    ],
                    ['name' => 'Regex', 'options' => ['pattern' => '/^[a-zA-Z]+$/']],
                ],
            ],
            'pin' => [
                'required'   => true,
                'filters'    => [
                    ['name' => 'StringTrim'],
                ],
                'validators' => [
                    [
                        'name'                   => 'StringLength',
                        'options'                => ['min' => 6, 'max' => 6],
                        'break_chain_on_failure' => true,
                    ],
                    ['name' => 'Digits'],
                ],
            ],
            'phone' => [
                'required'   => true,
                'filters'    => [
                    ['name' => 'StringTrim'],
                ],
                'validators' => [
                    [
                        'name'                   => 'StringLength',
                        'options'                => ['min' => 6, 'max' => 50],
                        'break_chain_on_failure' => true,
                    ],
                    ['name' => 'Digits'],
                ],
            ],
            'address' => [
                'required'   => true,
                'filters'    => [
                    ['name' => 'StringTrim'],
                ],
                'validators' => [
                    [
                        'name'                   => 'StringLength',
                        'options'                => ['min' => 3],
                        'break_chain_on_failure' => true,
                    ],
                ],
            ],
            'city' => [
                'required'   => true,
                'filters'    => [
                    ['name' => 'StringTrim'],
                ],
                'validators' => [
                    [
                        'name'                   => 'StringLength',
                        'options'                => ['min' => 3],
                        'break_chain_on_failure' => true,
                    ],
                ],
            ],
            'state' => [
                'required'   => true,
                'filters'    => [
                    ['name' => 'StringTrim'],
                ],
                'validators' => [
                    [
                        'name'                   => 'StringLength',
                        'options'                => ['min' => 3],
                        'break_chain_on_failure' => true,
                    ],
                ],
            ],
            'country' => [
                'required'   => true,
                'filters'    => [
                    ['name' => 'StringTrim'],
                ],
                'validators' => [
                    [
                        'name'                   => 'Digits',
                        'break_chain_on_failure' => true,
                    ],
                ],
            ],
        ];
        $this->inputFilterList->setFactory($this->factory);
        $this->factory->expects($this->once())->method('createInputFilter')->with($data)
            ->willReturn($this->inputFilter);

        $this->assertInstanceOf(InputFilterInterface::class, $this->inputFilterList->getForAddressStep());
    }

    public function testGetFactory()
    {
        $this->assertInstanceOf(Factory::class, $this->inputFilterList->getFactory());
    }
}
