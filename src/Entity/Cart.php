<?php

namespace Boodmo\Sales\Entity;

use Boodmo\User\Entity\User;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Ramsey\Uuid\Uuid;
use Zend\Stdlib\ArrayUtils;

/**
  * Class Cart.
 *
 * @ORM\Table(name="sales_cart")
 * @ORM\Entity(repositoryClass="Boodmo\Sales\Repository\CartRepository"))
 */
class Cart
{
    use TimestampableEntity;

    public const DEFAULT_SCOPE = 'web';
    /**
     * @var Uuid
     *
     * @ORM\Column(name="id", type="guid", nullable=false)
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="session_id", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $sessionId;

    /**
     * @var User
     *
     * @ORM\OneToOne(targetEntity="\Boodmo\User\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="user_id", onDelete="CASCADE")
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(name="scope", type="string", length=50, nullable=true, unique=false)
     */
    private $scope = self::DEFAULT_SCOPE;

    /**
     * @var array
     *
     * @ORM\Column(name="items", type="json_array", precision=0, scale=0, nullable=false, unique=false,
     *     options={"default" = "{}"}, columnDefinition="JSONB DEFAULT '{}'::jsonb NOT NULL")
     */
    private $items = [];

    /**
     * @var array
     *
     * @ORM\Column(name="address", type="json_array", nullable=false, unique=false,
     *     options={"default" = "{}", "jsonb": true})
     */
    private $address = [];

    /**
     * @var string
     *
     * @ORM\Column(name="step", type="string", length=50, nullable=true, unique=false)
     */
    private $step;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $email;

    /**
     * @var array
     *
     * @ORM\Column(name="payment", type="json_array", nullable=false, unique=false,
     *     options={"default" = "{}", "jsonb": true})
     */
    private $payment = [];

    /**
     * @var int
     *
     * @ORM\Column(name="order_id", type="integer", nullable=true, unique=false)
     */
    private $orderId;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="reminded", type="datetime", nullable=true)
     */
    private $reminded;

    /**
     * Cart constructor.
     */
    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
    }

    /**
     * Get id.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param string $id
     *
     * @return $this
     */
    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return $this
     */
    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function clear(bool $withOrder = false): void
    {
        $this->items = [];
        $this->address = [];
        $this->payment = [];
        $this->email = null;
        if ($withOrder) {
            $this->orderId = null;
        }
        $this->step = null;
        $this->reminded = null;
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param array $items
     *
     * @return $this
     */
    public function setItems(array $items): self
    {
        $this->items = $items;

        return $this;
    }

    /**
     * @return string
     */
    public function getStep(): ?string
    {
        return $this->step;
    }

    /**
     * @param $maxStep
     *
     * @return $this
     */
    public function setStep(?string $maxStep): self
    {
        $this->step = $maxStep;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return $this
     */
    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return \DateTimeInterface | null
     */
    public function getReminded(): ?DateTimeInterface
    {
        return $this->reminded;
    }

    /**
     * @param \DateTimeInterface $reminded
     *
     * @return $this
     */
    public function setReminded(?DateTimeInterface $reminded): self
    {
        $this->reminded = $reminded;
        return $this;
    }

    /**
     * @return string
     */
    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    /**
     * @param string $sessionId
     *
     * @return $this
     */
    public function setSessionId(?string $sessionId)
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    /**
     * @return string
     */
    public function getScope(): ?string
    {
        return $this->scope;
    }

    /**
     * @param string $scope
     *
     * @return $this
     */
    public function setScope(?string $scope): self
    {
        $this->scope = $scope;
        return $this;
    }

    /**
     * @return array
     */
    public function getAddress(): array
    {
        return $this->address;
    }

    /**
     * @param array $address
     *
     * @return $this
     */
    public function setAddress(array $address): self
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @return array
     */
    public function getPayment(): array
    {
        return $this->payment;
    }

    /**
     * @param array $payment
     *
     * @return $this
     */
    public function setPayment(array $payment): self
    {
        $this->payment = $payment;
        return $this;
    }

    /**
     * @return int
     */
    public function getOrderId(): ?int
    {
        return $this->orderId;
    }

    /**
     * @param int $orderId
     *
     * @return $this
     */
    public function setOrderId(?int $orderId): self
    {
        $this->orderId = $orderId;
        return $this;
    }

    public function merge(self $another): self
    {
        if ($this->getId() === $another->getId()) {
            return $this;
        }
        if ($another->getUser() !== null) {
            $this->setUser($another->getUser());
            $this->setItems(ArrayUtils::merge($this->getItems(), $another->getItems()));
            $this->setOrderId($another->getOrderId() ?? $this->getOrderId());
            $this->setStep($another->getStep() ?? $this->getStep());
            $this->setEmail($another->getEmail() ?? $this->getEmail());
            $this->setAddress(ArrayUtils::merge($this->getAddress(), $another->getAddress()));
            $this->setPayment($another->getPayment() ? $another->getPayment() : $this->getPayment());
            return $this;
        }
        $this->setItems(ArrayUtils::merge($another->getItems(), $this->getItems()));
        $this->setOrderId($this->getOrderId() ?? $another->getOrderId());
        $this->setStep($this->getStep() ?? $another->getStep());
        $this->setEmail($this->getEmail() ?? $another->getEmail());
        $this->setAddress(ArrayUtils::merge($another->getAddress(), $this->getAddress()));
        $this->setPayment($another->getPayment() ? $another->getPayment() : $this->getPayment());
        return $this;
    }
}
