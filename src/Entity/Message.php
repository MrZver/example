<?php

namespace Boodmo\Sales\Entity;

use Boodmo\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Class Messages.
 *
 * @ORM\Table(name="messages")
 * @ORM\Entity
 */
class Message
{
    use TimestampableEntity;

    public const TYPE_EMAIL = 'Email';
    public const TYPE_SMS = 'SMS';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint", precision=0, scale=0, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\SequenceGenerator(sequenceName="sales_message_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="destination", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $to = '';

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=60, precision=0, scale=0, nullable=false, unique=false)
     */
    private $type = '';

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $subject = '';

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text" , precision=0, scale=0, nullable=false, unique=false)
     */
    private $content = '';

    /**
     * AdminProfile.
     *
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="\Boodmo\User\Entity\User")
     * @ORM\JoinColumn(name="admin_profile_id", referencedColumnName="user_id")
     */
    private $adminProfile;

    /**
     * Order Package.
     *
     * @var OrderPackage
     *
     * @ORM\ManyToOne(targetEntity="\Boodmo\Sales\Entity\OrderPackage")
     * @ORM\JoinColumn(name="order_package_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $package;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return $this
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getTo(): string
    {
        return $this->to;
    }

    /**
     * @param string $to
     *
     * @return Message
     */
    public function setTo(string $to): self
    {
        $this->to = $to;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return Message
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     *
     * @return Message
     */
    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return User
     */
    public function getAdminProfile(): ?User
    {
        return $this->adminProfile;
    }

    /**
     * @param User $adminProfile
     *
     * @return Message
     */
    public function setAdminProfile(User $adminProfile)
    {
        $this->adminProfile = $adminProfile;

        return $this;
    }

    /**
     * @return OrderPackage
     */
    public function getPackage(): ?OrderPackage
    {
        return $this->package;
    }

    /**
     * @param OrderPackage $package
     *
     * @return Message
     */
    public function setPackage(OrderPackage $package)
    {
        $this->package = $package;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return Message
     */
    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getArrayCopy(): array
    {
        return get_object_vars($this);
    }
}
