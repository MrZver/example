<?php

namespace Boodmo\Sales\Entity;

use Boodmo\Sales\Model\Workflow\Note\NotesableEntityIntarface;
use Boodmo\Sales\Model\Workflow\Note\NotesableEntityTrait;
use Boodmo\User\Entity\UserProfile\Supplier;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * Class OrderBid
 *
 * @ORM\Table(name="sales_order_bids")
 * @ORM\Entity(repositoryClass="Boodmo\Sales\Repository\OrderBidRepository")
 */
class OrderBid implements NotesableEntityIntarface
{
    use TimestampableEntity, NotesableEntityTrait;

    public const STATUS_OPEN      = 'open';
    public const STATUS_ACCEPTED  = 'accepted';
    public const STATUS_REJECTED  = 'rejected';
    public const STATUS_MISSED    = 'missed';
    public const STATUS_CANCELLED = 'cancelled';

    public const NOTE_CONTEXT = 'BIDS';

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
     * @ORM\Column(name="status", type="string", length=255, nullable=true, unique=false)
     */
    private $status = self::STATUS_OPEN;

    /**
     * @var int
     *
     * @ORM\Column(name="price", type="integer", precision=10, scale=3, nullable=false, unique=false)
     */
    private $price = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="cost", type="integer", precision=10, scale=3, nullable=true, unique=false)
     */
    private $cost = 0;

    /**
     * @var OrderItem
     *
     * @ORM\ManyToOne(targetEntity="\Boodmo\Sales\Entity\OrderItem", inversedBy="bids", cascade={"persist"})
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id", nullable=false)
     */
    private $orderItem;

    /**
     * SupplierProfile
     *
     * @var Supplier
     *
     * @ORM\ManyToOne(targetEntity="\Boodmo\User\Entity\UserProfile\Supplier")
     * @ORM\JoinColumn(name="supplier_profile_id", referencedColumnName="id")
     */
    private $supplierProfile;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="dispatch_date", type="date", nullable=true)
     */
    private $dispatchDate;

    /**
     * @var int
     *
     * @ORM\Column(name="delivery_days", type="smallint", precision=0, scale=0, nullable=true)
     */
    private $deliveryDays;

    /**
     * @var string
     *
     * @ORM\Column(name="brand_name", type="string", precision=0, scale=0, nullable=true)
     */
    private $brand;

    /**
     * @var string
     *
     * @ORM\Column(name="number", type="string", precision=0, scale=0, nullable=true)
     */
    private $number;

    /**
     * @var int
     *
     * @ORM\Column(name="gst", type="smallint", precision=0, scale=0, nullable=true)
     */
    private $gst;

    public function __construct()
    {
        $this->id = (string) Uuid::uuid4();
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
     * @param string $id
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setId(string $id): self
    {
        if (!Uuid::isValid($id)) {
            throw new \InvalidArgumentException('ID must have uuid4 format.');
        }
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status ?? '';
    }

    /**
     * @param string $status
     *
     * @return $this
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return int
     */
    public function getPrice(): int
    {
        return $this->price ?? 0;
    }

    /**
     * @param int $price
     *
     * @return $this|OrderBid
     */
    public function setPrice(int $price): self
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @return int
     */
    public function getCost(): int
    {
        return $this->cost ?? 0;
    }

    /**
     * @param int $cost
     *
     * @return $this|OrderBid
     */
    public function setCost(int $cost): self
    {
        $this->cost = $cost;

        return $this;
    }

    /**
     * @return OrderItem
     */
    public function getOrderItem(): OrderItem
    {
        return $this->orderItem;
    }

    /**
     * @param OrderItem $orderItem
     * @return $this
     */
    public function setOrderItem(OrderItem $orderItem): self
    {
        $this->orderItem = $orderItem;
        return $this;
    }

    /**
     * @return Supplier
     */
    public function getSupplierProfile(): Supplier
    {
        return $this->supplierProfile;
    }

    /**
     * @param $supplierProfile
     *
     * @return $this
     */
    public function setSupplierProfile(Supplier $supplierProfile): self
    {
        $this->supplierProfile = $supplierProfile;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDispatchDate(): ?DateTime
    {
        return $this->dispatchDate;
    }

    /**
     * @param DateTime $dispatchDate
     *
     * @return OrderBid
     */
    public function setDispatchDate(DateTime $dispatchDate): self
    {
        $this->dispatchDate = $dispatchDate;
        return $this;
    }

    /**
     * @return int
     */
    public function getDeliveryDays(): ?int
    {
        return $this->deliveryDays;
    }

    /**
     * @param int $deliveryDays
     * @return $this
     */
    public function setDeliveryDays(int $deliveryDays)
    {
        $this->deliveryDays = $deliveryDays;
        return $this;
    }

    /**
     * @return string
     */
    public function getBrand(): ?string
    {
        return $this->brand;
    }

    /**
     * @param string $brand
     * @return OrderBid
     */
    public function setBrand(string $brand): OrderBid
    {
        $this->brand = $brand;
        return $this;
    }

    /**
     * @return string
     */
    public function getNumber(): ?string
    {
        return $this->number;
    }

    /**
     * @param string $number
     * @return OrderBid
     */
    public function setNumber(string $number): OrderBid
    {
        $this->number = $number;
        return $this;
    }

    /**
     * @return int
     */
    public function getGst(): ?int
    {
        return $this->gst;
    }

    /**
     * @param int $gst
     * @return OrderBid
     */
    public function setGst(?int $gst): OrderBid
    {
        $this->gst = $gst;
        return $this;
    }
}
