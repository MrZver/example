<?php

namespace Boodmo\SalesTest\Model\Workflow\Order\Handler;

use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Model\Workflow\Order\Command\CreateRmaCommand;
use Boodmo\Sales\Model\Workflow\Order\Handler\CreateRmaHandler;
use Boodmo\Sales\Service\OrderService;
use Boodmo\User\Entity\User;
use PHPUnit\Framework\TestCase;

class CreateRmaHandlerTest extends TestCase
{
    /**
     * @var CreateRmaHandler
     */
    protected $handler;

    /**
     * @var OrderService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderService;

    public function setUp()
    {
        $this->orderService = $this->createPartialMock(OrderService::class, ['loadOrderItem', 'save']);
        $this->handler = new CreateRmaHandler($this->orderService);
    }

    /**
     * @dataProvider invokeException
     */
    public function testInvokeException($expected, $data)
    {
        $orderItem = (new OrderItem())->setQty($data['item']['qty'])->setId($data['item']['id'])
            ->setPackage(
                (new OrderPackage())->setBundle(new OrderBundle())
            );
        $this->orderService->method('loadOrderItem')->willReturn($orderItem);
        $command = new CreateRmaCommand(
            'ef9e51dd-793c-4027-a5a6-a30e092bd6b0',
            $data['command']['intent'],
            $data['command']['reason'],
            $data['command']['note'],
            $data['command']['qty'],
            $data['command']['user']
        );
        if (!empty($expected['exception_message'])) {
            $this->expectExceptionMessage($expected['exception_message']);
        } else {
            $this->orderService->expects($this->once())->method('save');
        }
        ($this->handler)($command);
    }

    public function invokeException()
    {
        return [
            'test1' => [
                'expected' => [
                    'exception_message' => 'Incorrect values for return (item id: ef9e51dd-793c-4027-a5a6-a30e092bd6b0)'
                ],
                'data' => [
                    'item' => ['qty' => 1, 'id' => 'ef9e51dd-793c-4027-a5a6-a30e092bd6b0'],
                    'command' => [
                        'intent' => 'test',
                        'reason' => 'test',
                        'note' => 'test',
                        'qty' => 1,
                        'user' => new User(),
                    ]
                ]
            ],
            'test2' => [
                'expected' => [
                    'exception_message' => 'Incorrect values for return (item id: ef9e51dd-793c-4027-a5a6-a30e092bd6b0)'
                ],
                'data' => [
                    'item' => ['qty' => 1, 'id' => 'ef9e51dd-793c-4027-a5a6-a30e092bd6b0'],
                    'command' => [
                        'intent' => 'MONEY_RETURN',
                        'reason' => 'BROKEN_PACKAGE',
                        'note' => 'test',
                        'qty' => 2,
                        'user' => new User(),
                    ]
                ]
            ],
            'test3' => [
                'expected' => [
                    'exception_message' => ''
                ],
                'data' => [
                    'item' => ['qty' => 4, 'id' => 'ef9e51dd-793c-4027-a5a6-a30e092bd6b0'],
                    'command' => [
                        'intent' => 'MONEY_RETURN',
                        'reason' => 'BROKEN_PACKAGE',
                        'note' => 'test',
                        'qty' => 3,
                        'user' => new User(),
                    ]
                ]
            ],
            'test4' => [
                'expected' => [
                    'exception_message' => ''
                ],
                'data' => [
                    'item' => ['qty' => 4, 'id' => 'ef9e51dd-793c-4027-a5a6-a30e092bd6b0'],
                    'command' => [
                        'intent' => 'REPLACE_WITH_PART',
                        'reason' => 'INACCURATE_DESCRIPTION',
                        'note' => 'test',
                        'qty' => 3,
                        'user' => new User(),
                    ]
                ]
            ],
            'test5' => [
                'expected' => [
                    'exception_message' => ''
                ],
                'data' => [
                    'item' => ['qty' => 4, 'id' => 'ef9e51dd-793c-4027-a5a6-a30e092bd6b0'],
                    'command' => [
                        'intent' => 'REPLACE_WITH_PART',
                        'reason' => 'ITEM_DEFECTIVE',
                        'note' => 'test',
                        'qty' => 3,
                        'user' => new User(),
                    ]
                ]
            ],
            'test6' => [
                'expected' => [
                    'exception_message' => ''
                ],
                'data' => [
                    'item' => ['qty' => 4, 'id' => 'ef9e51dd-793c-4027-a5a6-a30e092bd6b0'],
                    'command' => [
                        'intent' => 'REPLACE_WITH_PART',
                        'reason' => 'ITEM_LATE',
                        'note' => 'test',
                        'qty' => 3,
                        'user' => new User(),
                    ]
                ]
            ],
            'test7' => [
                'expected' => [
                    'exception_message' => ''
                ],
                'data' => [
                    'item' => ['qty' => 4, 'id' => 'ef9e51dd-793c-4027-a5a6-a30e092bd6b0'],
                    'command' => [
                        'intent' => 'REPLACE_WITH_PART',
                        'reason' => 'BUY_MISTAKE',
                        'note' => 'test',
                        'qty' => 3,
                        'user' => new User(),
                    ]
                ]
            ],
            'test8' => [
                'expected' => [
                    'exception_message' => ''
                ],
                'data' => [
                    'item' => ['qty' => 4, 'id' => 'ef9e51dd-793c-4027-a5a6-a30e092bd6b0'],
                    'command' => [
                        'intent' => 'REPLACE_WITH_PART',
                        'reason' => 'WRONG_SENT',
                        'note' => 'test',
                        'qty' => 3,
                        'user' => new User(),
                    ]
                ]
            ],
            'test9' => [
                'expected' => [
                    'exception_message' => ''
                ],
                'data' => [
                    'item' => ['qty' => 4, 'id' => 'ef9e51dd-793c-4027-a5a6-a30e092bd6b0'],
                    'command' => [
                        'intent' => 'REPLACE_WITH_PART',
                        'reason' => 'WRONG_RECEIVED',
                        'note' => 'test',
                        'qty' => 3,
                        'user' => new User(),
                    ]
                ]
            ],
            'test10' => [
                'expected' => [
                    'exception_message' => ''
                ],
                'data' => [
                    'item' => ['qty' => 4, 'id' => 'ef9e51dd-793c-4027-a5a6-a30e092bd6b0'],
                    'command' => [
                        'intent' => 'REPLACE_WITH_PART',
                        'reason' => 'NO_NEEDED',
                        'note' => 'test',
                        'qty' => 3,
                        'user' => new User(),
                    ]
                ]
            ],
        ];
    }
}
