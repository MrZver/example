<?php

namespace Boodmo\SalesTest\Model\Workflow\Note;

use Boodmo\Sales\Model\Workflow\Note\NotesMessage;
use Boodmo\User\Entity\User;
use PHPUnit\Framework\TestCase;

class NotesMessageTest extends TestCase
{
    /**
     * @var array
     */
    protected $defaultData;

    /**
     * @var NotesMessage
     */
    protected $noteMessage;

    public function setUp()
    {
        $this->defaultData = [
            'context' => 'some_context',
            'message' => 'some_message',
            'author' => (new User())->setId(1)
        ];
        $this->noteMessage = new NotesMessage(...array_values($this->defaultData));
    }

    public function testGetContext()
    {
        $this->assertEquals($this->defaultData['context'], $this->noteMessage->getContext());
    }

    public function testGetMessage()
    {
        $this->assertEquals($this->defaultData['message'], $this->noteMessage->getMessage());
    }

    public function testGetAuthor()
    {
        $this->assertEquals($this->defaultData['author'], $this->noteMessage->getAuthor());
    }

    public function testGetDate()
    {
        $this->assertInstanceOf(\DateTime::class, $this->noteMessage->getDate());
    }

    public function testToArray()
    {
        $this->assertEquals(
            [
                'message'   => $this->defaultData['message'],
                'date'      => $this->noteMessage->getDate()->getTimestamp(),
                'author'    => $this->defaultData['author']->getEmail(),
            ],
            $this->noteMessage->toArray()
        );
    }

    public function testToArrayDefaultAuthor()
    {
        $noteMessage = new NotesMessage('some_context2', 'some_message2');
        $this->assertEquals(
            [
                'message'   => 'some_message2',
                'date'      => $noteMessage->getDate()->getTimestamp(),
                'author'    => 'system@boodmo.com',
            ],
            $noteMessage->toArray()
        );
    }
}
