<?php

namespace Boodmo\Sales\Entity;

use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Model\Workflow\Note\NotesableEntityIntarface;
use Boodmo\Sales\Model\Workflow\EntityStatusTrait;
use Boodmo\Sales\Model\Workflow\Note\NotesableEntityTrait;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Model\Workflow\Status\StatusHistoryInterface;
use Boodmo\Sales\Model\Workflow\Status\StatusProviderAggregateInterface;
use Boodmo\Sales\Model\Workflow\Status\StatusProviderInterface;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Money\Currency;
use Money\Money;
use Ramsey\Uuid\Uuid;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Class OrderItem.
 *
 * @ORM\Table(name="sales_order_items")
 * @ORM\Entity(repositoryClass="Boodmo\Sales\Repository\OrderItemRepository")
 */
class OrderItem implements StatusProviderInterface, StatusHistoryInterface, NotesableEntityIntarface, FlagsableEntityInterface
{
    use TimestampableEntity, EntityStatusTrait, NotesableEntityTrait, FlagsableEntityTrait;
    /**
     * @var Uuid
     *
     * @ORM\Column(name="id", type="guid", nullable=false)
     * @ORM\Id
     */
    private $id;

    /**
     * @var OrderPackage
     *
     * @ORM\ManyToOne(targetEntity="\Boodmo\Sales\Entity\OrderPackage", inversedBy="items")
     * @ORM\JoinColumn(name="order_package_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $package;

    /**
     * @var int
     *
     * @ORM\Column(name="product_id", type="bigint", precision=0, scale=0, nullable=false)
     */
    private $productId;

    /**
     * @var int
     *
     * @ORM\Column(name="part_id", type="bigint", precision=0, scale=0)
     */
    private $partId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="brand_name", type="string", precision=0, scale=0)
     */
    private $brand;

    /**
     * @var string
     *
     * @ORM\Column(name="number", type="string", precision=0, scale=0, nullable=true)
     */
    private $number;

    /**
     * @var string
     *
     * @ORM\Column(name="family", type="string", precision=0, scale=0, nullable=true)
     */
    private $family;

    /**
     * @var int
     *
     * @ORM\Column(name="qty", type="integer", precision=0, scale=0, nullable=false)
     */
    private $qty = 1;

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
     * @var float
     *
     * @ORM\Column(name="origin_price", type="integer", precision=10, scale=3, nullable=true, unique=false)
     */
    private $originPrice = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="delivery_price", type="integer", precision=10, scale=3, nullable=false, unique=false)
     */
    private $deliveryPrice = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="discount", type="integer", nullable=false, unique=false, options={"default" : 0})
     */
    private $discount = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="base_price", type="integer", precision=10, scale=3, nullable=true, unique=false)
     */
    private $basePrice = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="base_cost", type="integer", precision=10, scale=3, nullable=true, unique=false)
     */
    private $baseCost = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="base_origin_price", type="integer", precision=10, scale=3, nullable=true, unique=false)
     */
    private $baseOriginPrice = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="base_delivery_price", type="integer", precision=10, scale=3, nullable=true, unique=false)
     */
    private $baseDeliveryPrice = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="base_discount", type="integer", nullable=false, unique=false, options={"default" : 0})
     */
    private $baseDiscount = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="dropshipping_status", type="string", length=50, precision=0, scale=0, nullable=true)
     */
    private $dropShippingStatus;

    /**
     * @var string
     *
     * @ORM\Column(name="supplier_order_status", type="string", length=50, precision=0, scale=0, nullable=true)
     */
    private $supplierOrderStatus;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="dispatch_date", type="date", nullable=true)
     */
    private $dispatchDate;

    /**
     * @var \Boodmo\Sales\Entity\CancelReason
     *
     * @ORM\ManyToOne(targetEntity="Boodmo\Sales\Entity\CancelReason", fetch="EAGER")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="cancel_reason_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $cancelReason;

    /**
     * @var bool
     *
     * @ORM\Column(name="locked", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    private $locked = false;

    /**
     * @var array
     * @deprecated Remove after multicurrency release
     *
     * @ORM\Column(name="workflow_history", type="json_array", precision=0, scale=0, nullable=false, unique=false,
     *     options={"default" = "{}"}, columnDefinition="JSONB DEFAULT '{}'::jsonb NOT NULL")
     */
    protected $workflowHistory = [];

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="\Boodmo\Sales\Entity\OrderRma",
     *     mappedBy="orderItem", fetch="EXTRA_LAZY", cascade={"persist", "remove"})
     */
    private $rmaList;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="\Boodmo\Sales\Entity\OrderBid",
     *     mappedBy="orderItem", fetch="EXTRA_LAZY", cascade={"persist", "remove"})
     */
    private $bids;

    /**
     * @var \DateTimeImmutable
     * @ORM\Column(name="confirmation_date", type="datetime_immutable", nullable=true)
     */
    protected $confirmationDate;

    public function __construct()
    {
        $this->id = (string) Uuid::uuid4();
        $this->rmaList = new ArrayCollection();
        $this->bids = new ArrayCollection();
    }

    /**
     * Get id.
     *
     * @return Uuid
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
     * @return OrderPackage
     */
    public function getPackage(): ?OrderPackage
    {
        return $this->package;
    }

    public function getParent(): ?StatusProviderAggregateInterface
    {
        return $this->package;
    }

    /**
     * @param mixed $package
     *
     * @return $this
     */
    public function setPackage(OrderPackage $package): self
    {
        $this->package = $package;

        return $this;
    }

    /**
     * @return int
     */
    public function getProductId(): int
    {
        return (int)$this->productId;
    }

    /**
     * @param int $productId
     *
     * @return $this
     */
    public function setProductId(int $productId): self
    {
        $this->productId = $productId;

        return $this;
    }

    /**
     * @return int
     */
    public function getPartId(): int
    {
        return (int)$this->partId;
    }

    /**
     * @param int $partId
     *
     * @return $this|OrderItem
     */
    public function setPartId(int $partId): self
    {
        $this->partId = $partId;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return int
     */
    public function getQty(): int
    {
        return $this->qty;
    }

    /**
     * @param int $qty
     *
     * @return $this|OrderItem
     */
    public function setQty(int $qty): self
    {
        $this->qty = $qty;
        $this->recalculateBills();
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
     * @return $this|OrderItem
     */
    public function setPrice(int $price): self
    {
        $this->price = $price;
        $this->recalculateBills();
        return $this;
    }

    /**
     * @return int
     */
    public function getDeliveryPrice(): int
    {
        return $this->deliveryPrice ?? 0;
    }

    /**
     * @param int $deliveryPrice
     *
     * @return $this|OrderItem
     */
    public function setDeliveryPrice(int $deliveryPrice): self
    {
        $this->deliveryPrice = $deliveryPrice;
        $this->recalculateBills();
        return $this;
    }

    /**
     * @return int
     */
    public function getSubTotal()
    {
        return $this->getPrice() * $this->getQty();
    }

    /**
     * @return int
     */
    public function getCostTotal()
    {
        return $this->getCost() * $this->getQty();
    }

    public function getDeliveryTotal()
    {
        return $this->getDeliveryPrice() * $this->getQty();
    }

    public function getGrandTotal(): int
    {
        return $this->getSubTotal() + $this->getDeliveryTotal() - $this->getDiscount();
    }

    /**
     * @return int
     */
    public function getBaseSubTotal()
    {
        return $this->getBasePrice() * $this->getQty();
    }

    /**
     * @return int
     */
    public function getBaseDeliveryTotal()
    {
        return $this->getBaseDeliveryPrice() * $this->getQty();
    }

    /**
     * @return string
     */
    public function getBrand(): string
    {
        return $this->brand ?? '';
    }

    /**
     * @param string $brand
     *
     * @return $this|OrderItem
     */
    public function setBrand(string $brand): self
    {
        $this->brand = $brand;

        return $this;
    }

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number ?? '';
    }

    /**
     * @param string $number
     *
     * @return $this|OrderItem
     */
    public function setNumber(string $number): self
    {
        $this->number = $number;

        return $this;
    }

    /**
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->getStatusList()->exists(StatusEnum::build(StatusEnum::CANCELLED));
    }

    /**
     * @return bool
     */
    public function isReplaced(): bool
    {
        $cancelReason = $this->getCancelReason();
        $statusHistory = $this->getStatusHistory();
        $isReplaced = $this->isCancelled() && !empty($statusHistory[0]['context']['child']);
        return ($cancelReason && $cancelReason->getId() === CancelReason::ITEM_WAS_REPLACED) || $isReplaced;
    }

    /**
     * @return bool
     */
    public function isAnyCancelled(): bool
    {
        return $this->getStatusList()->exists(StatusEnum::build(StatusEnum::CANCELLED))
            || $this->getStatusList()->exists(StatusEnum::build(StatusEnum::CANCEL_REQUESTED_USER))
            || $this->getStatusList()->exists(StatusEnum::build(StatusEnum::CANCEL_REQUESTED_SUPPLIER));
    }

    /**
     * @return int
     */
    public function getOriginPrice(): int
    {
        return $this->originPrice ?? 0;
    }

    /**
     * @param int $originPrice
     *
     * @return $this|OrderItem
     */
    public function setOriginPrice(int $originPrice): self
    {
        $this->originPrice = $originPrice;

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
     * @return $this|OrderItem
     */
    public function setCost(int $cost): self
    {
        $this->cost = $cost;

        return $this;
    }

    public function getDispatchDate(): ?DateTime
    {
        return $this->dispatchDate;
    }

    /**
     * @param string $dispatchDate
     *
     * @return $this
     */
    public function setDispatchDate(DateTime $dispatchDate)
    {
        $this->dispatchDate = $dispatchDate;
        if ($this->package !== null) {
            $this->package->calculateShippingETA();
        }
        return $this;
    }

    public function getCancelReason(): ?CancelReason
    {
        return $this->cancelReason;
    }

    public function setCancelReason(?CancelReason $cancelReason): self
    {
        $this->cancelReason = $cancelReason;

        return $this;
    }

    public function getLocked() : bool
    {
        return (bool)$this->locked;
    }

    public function setLocked(bool $locked)
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * @return int
     */
    public function getDiscount(): int
    {
        return $this->discount ?? 0;
    }

    /**
     * @param int $discount
     *
     * @return $this|OrderItem
     */
    public function setDiscount(int $discount): self
    {
        $this->discount = $discount;
        $package = $this->getPackage();
        if (!is_null($package)) {
            $package->getBundle()->recalculateBills();
        }
        return $this;
    }

    /**
     * @return int
     */
    public function getBasePrice(): int
    {
        return $this->basePrice ?? 0;
    }

    /**
     * @param int $basePrice
     *
     * @return $this|OrderItem
     */
    public function setBasePrice(int $basePrice): self
    {
        $this->basePrice = $basePrice;
        $package = $this->getPackage();
        if (!is_null($package)) {
            $package->getBundle()->recalculateBills();
        }
        return $this;
    }

    /**
     * @return int
     */
    public function getBaseCost(): int
    {
        return $this->baseCost ?? 0;
    }

    /**
     * @param int $baseCost
     *
     * @return $this|OrderItem
     */
    public function setBaseCost(int $baseCost): self
    {
        $this->baseCost = $baseCost;
        return $this;
    }

    /**
     * @return int
     */
    public function getBaseOriginPrice(): int
    {
        return $this->baseOriginPrice ?? 0;
    }

    /**
     * @param int $baseOriginPrice
     *
     * @return $this|OrderItem
     */
    public function setBaseOriginPrice(int $baseOriginPrice): self
    {
        $this->baseOriginPrice = $baseOriginPrice;
        return $this;
    }

    /**
     * @return int
     */
    public function getBaseDeliveryPrice(): int
    {
        return $this->baseDeliveryPrice ?? 0;
    }

    /**
     * @param int $baseDeliveryPrice
     *
     * @return $this|OrderItem
     */
    public function setBaseDeliveryPrice(int $baseDeliveryPrice): self
    {
        $this->baseDeliveryPrice = $baseDeliveryPrice;
        return $this;
    }

    /**
     * @return int
     */
    public function getBaseDiscount(): int
    {
        return $this->baseDiscount ?? 0;
    }

    /**
     * @param int $baseDiscount
     *
     * @return $this|OrderItem
     */
    public function setBaseDiscount(int $baseDiscount): self
    {
        $this->baseDiscount = $baseDiscount;
        return $this;
    }

    /**
     * @return string
     */
    public function getFamily(): ?string
    {
        return $this->family;
    }

    /**
     * @param string $family
     * @return $this
     */
    public function setFamily(string $family)
    {
        $this->family = $family;
        return $this;
    }

    public function getMoney(int $amount): Money
    {
        $currency = new Currency(
            $this->getPackage() ? $this->getPackage()->getCurrency() : MoneyService::BASE_CURRENCY
        );
        return new Money($amount, $currency);
    }

    public function __clone()
    {
        $this->package = null;
        $this->cancelReason = null;
        $this->id = Uuid::uuid4();
        $this->bids = new ArrayCollection();
    }

    /**
     * @return OrderRma[]|ArrayCollection|Collection
     */
    public function getRmaList(): Collection
    {
        return $this->rmaList;
    }

    /**
     * @param ArrayCollection $rmaList
     *
     * @return $this|OrderItem
     */
    public function setRmaList(ArrayCollection $rmaList): self
    {
        $this->rmaList = $rmaList;
        return $this;
    }

    /**
     * @param OrderRma $rma
     *
     * @return $this|OrderItem
     */
    public function addRma(OrderRma $rma): self
    {
        $rma->setOrderItem($this);
        $rma->setNumber($rma->generateNumber());
        $this->rmaList->add($rma);
        return $this;
    }

    /**
     * @return OrderBid[]|ArrayCollection|Collection
     */
    public function getBids(): Collection
    {
        return $this->bids;
    }

    /**
     * @param ArrayCollection $bids
     *
     * @return $this|OrderItem
     */
    public function setBids(ArrayCollection $bids): self
    {
        $this->bids = $bids;
        return $this;
    }

    /**
     * @param OrderBid $bid
     *
     * @return $this|OrderItem
     */
    public function addBid(OrderBid $bid): self
    {
        $bid->setOrderItem($this);
        $this->bids->add($bid);
        return $this;
    }

    /**
     * @param OrderBid $bid
     *
     * @return $this
     */
    public function removeBid(OrderBid $bid): self
    {
        $this->bids->removeElement($bid);
        return $this;
    }

    public function createAcceptedBid(): OrderBid
    {
        foreach ($this->getBids() as $bid) {
            if (!\in_array($bid->getStatus(), [OrderBid::STATUS_MISSED, OrderBid::STATUS_CANCELLED, OrderBid::STATUS_REJECTED], true)) {
                $bid->setStatus(OrderBid::STATUS_REJECTED);
            }
        }
        return (new OrderBid())
            ->setSupplierProfile($this->getPackage()->getSupplierProfile())
            ->setPrice($this->getPrice())
            ->setCost($this->getCost())
            ->setBrand($this->getBrand())
            ->setNumber($this->getNumber())
            ->setDeliveryDays($this->getPackage()->getDeliveryDays())
            ->setDispatchDate($this->getDispatchDate() ?? new \DateTime())
            ->setStatus(OrderBid::STATUS_ACCEPTED);
    }

    /**
     * @return OrderBid|null
     */
    public function getItemAcceptedBid(): ?OrderBid
    {
        $bids = $this->getBids()->filter(function (OrderBid $orderBid) {
            return $orderBid->getStatus() === OrderBid::STATUS_ACCEPTED;
        });
        return $bids->count() > 0 ? $bids->first() : null;
    }

    /**
     * @param string $id
     * @return OrderBid|null
     */
    public function getItemBidById(string $id): ?OrderBid
    {
        $bids = $this->getBids()->filter(function (OrderBid $orderBid) use ($id) {
            return $orderBid->getId() === $id;
        });
        return $bids->count() > 0 ? $bids->first() : null;
    }

    /**
     * @return int
     */
    public function getRmaTotalQty(): int
    {
        $result = 0;
        foreach ($this->getRmaList() as $rmaItem) {
            $result += $rmaItem->getQty();
        }
        return $result;
    }

    /**
     * Returns confirmationDate
     *
     * @return \DateTimeImmutable
     */
    public function getConfirmationDate(): ?\DateTimeImmutable
    {
        return $this->confirmationDate;
    }

    /**
     * Set confirmationDate
     *
     * @param  \DateTimeImmutable $confirmationDate
     * @return $this
     */
    public function setConfirmationDate(\DateTimeImmutable $confirmationDate): self
    {
        $this->confirmationDate = $confirmationDate;
        return $this;
    }

    private function recalculateBills(): void
    {
        if ($package = $this->getPackage() and $bundle = $package->getBundle()) {
            $bundle->recalculateBills();
        }
    }
}
