<?php

namespace Boodmo\SalesTest\Entity;

use Boodmo\Sales\Entity\Message;
use Boodmo\User\Entity\User as AdminProfile;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\User\Entity\User;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    /**
     * @var Message
     */
    protected $message;

    /**
     * @covers \Boodmo\Sales\Entity\Message::__construct
     */
    public function setUp()
    {
        $this->message = new Message();
    }

    /**
     * @covers \Boodmo\Sales\Entity\Message::setId
     * @covers \Boodmo\Sales\Entity\Message::getId
     */
    public function testSetGetId()
    {
        $this->assertEquals($this->message, $this->message->setId(1));
        $this->assertEquals(1, $this->message->getId());
    }

    /**
     * @covers \Boodmo\Sales\Entity\Message::setTo
     * @covers \Boodmo\Sales\Entity\Message::getTo
     */
    public function testSetGetTo()
    {
        $this->assertEquals($this->message, $this->message->setTo("ashutosh@balwariasecurity.com"));
        $this->assertEquals("ashutosh@balwariasecurity.com", $this->message->getTo());
    }

    /**
     * @covers \Boodmo\Sales\Entity\Message::setType
     * @covers \Boodmo\Sales\Entity\Message::getType
     */
    public function testSetGetType()
    {
        $this->assertEquals($this->message, $this->message->setType("sms"));
        $this->assertEquals("sms", $this->message->getType());
    }

    /**
     * @covers \Boodmo\Sales\Entity\Message::setSubject
     * @covers \Boodmo\Sales\Entity\Message::getSubject
     */
    public function testSetGetSubject()
    {
        $this->assertEquals($this->message, $this->message->setSubject("Test subject"));
        $this->assertEquals("Test subject", $this->message->getSubject());
    }

    /**
     * @covers \Boodmo\Sales\Entity\Message::setContent
     * @covers \Boodmo\Sales\Entity\Message::getContent
     */
    public function testSetGetContent()
    {
        $this->assertEquals($this->message, $this->message->setContent("Test content"));
        $this->assertEquals("Test content", $this->message->getContent());
    }

    /**
     * @covers \Boodmo\Sales\Entity\Message::setAdminProfile
     * @covers \Boodmo\Sales\Entity\Message::getAdminProfile
     */
    public function testSetGetAdminProfile()
    {
        $adminProfile = new AdminProfile();
        $this->assertEquals($this->message, $this->message->setAdminProfile($adminProfile));
        $this->assertEquals($adminProfile, $this->message->getAdminProfile());
    }

    /**
     * @covers \Boodmo\Sales\Entity\Message::setPackage
     * @covers \Boodmo\Sales\Entity\Message::getPackage
     */
    public function testSetGetPackage()
    {
        $package = new OrderPackage();
        $this->assertEquals($this->message, $this->message->setPackage($package));
        $this->assertEquals($package, $this->message->getPackage());
    }

    public function testGetArrayCopy()
    {
        $this->assertEquals(
            [
                'id' => null,
                'to' => '',
                'type' => '',
                'subject' => '',
                'content' => '',
                'adminProfile' => null,
                'package' => null,
                'createdAt' => null,
                'updatedAt' => null,
            ],
            $this->message->getArrayCopy()
        );

        $user = (new User())->setId(1);
        $package = (new OrderPackage())->setId(1);
        $created = new \DateTime('2017-10-14');
        $updated = new \DateTime('2017-10-15');
        $this->message
            ->setId(1)
            ->setTo('test_to')
            ->setType('test_type')
            ->setSubject('test_subject')
            ->setContent('test_content')
            ->setAdminProfile($user)
            ->setPackage($package)
            ->setCreatedAt($created)
            ->setUpdatedAt($updated);
        $this->assertEquals(
            [
                'id' => 1,
                'to' => 'test_to',
                'type' => 'test_type',
                'subject' => 'test_subject',
                'content' => 'test_content',
                'adminProfile' => $user,
                'package' => $package,
                'createdAt' => $created,
                'updatedAt' => $updated,
            ],
            $this->message->getArrayCopy()
        );
    }
}
