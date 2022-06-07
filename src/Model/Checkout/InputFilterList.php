<?php

namespace Boodmo\Sales\Model\Checkout;

use Zend\InputFilter\Factory;
use Zend\InputFilter\InputFilterInterface;

class InputFilterList
{
    /**
     * @var Factory
     */
    private $factory;

    public function getForPin(): InputFilterInterface
    {
        return $this->getFactory()->createInputFilter([
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
        ]);
    }

    public function getForEmailStep(): InputFilterInterface
    {
        return $this->getFactory()->createInputFilter([
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
        ]);
    }

    public function getForAddressStep(): InputFilterInterface
    {
        return $this->getFactory()->createInputFilter([
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
                        'name' => 'Digits',
                        'break_chain_on_failure' => true,
                    ],
                ],
            ],
        ]);
    }

    public function setFactory(Factory $factory) : self
    {
        $this->factory = $factory;
        return $this;
    }

    public function getFactory() : Factory
    {
        if (null === $this->factory) {
            $this->setFactory(new Factory());
        }
        return $this->factory;
    }
}
