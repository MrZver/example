<?php

namespace Boodmo\Sales\Entity;

use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Model\Workflow\EntityStatusTrait;
use Boodmo\Sales\Model\Workflow\Note\NotesableEntityIntarface;
use Boodmo\Sales\Model\Workflow\Note\NotesableEntityTrait;
use Boodmo\Sales\Model\Workflow\Status\Status;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Model\Workflow\Status\StatusProviderAggregateInterface;
use Boodmo\Sales\Model\Workflow\Status\StatusProviderInterface;
use Boodmo\Shipping\Entity\ShippingBox;
use Boodmo\User\Entity\Address;
use Boodmo\User\Entity\UserProfile\Supplier;
use Closure;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Doctrine\ORM\Mapping as ORM;
use Boodmo\Sales\Model\Workflow\Note\NotesMessage;

/**
 * Class OrderPackage.
 *
 * @ORM\Table(name="sales_order_packages")
 * @ORM\Entity(repositoryClass="Boodmo\Sales\Repository\OrderPackageRepository")
 */
class OrderPackage implements StatusProviderAggregateInterface, NotesableEntityIntarface, FlagsableEntityInterface
{
    use TimestampableEntity, EntityStatusTrait, NotesableEntityTrait, FlagsableEntityTrait;

    public const DEFAULT_STATE = 'HARYANA';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint", precision=0, scale=0, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\SequenceGenerator(sequenceName="sales_order_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var OrderBundle
     *
     * @ORM\ManyToOne(targetEntity="\Boodmo\Sales\Entity\OrderBundle", inversedBy="packages")
     * @ORM\JoinColumn(name="order_bundle_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $bundle;

    /**
     * @var int
     *
     * @ORM\Column(name="increment_number", type="smallint", precision=0, scale=0, nullable=false)
     */
    private $number;

    /**
     * SoldBy SupplierProfile
     *
     * @var Supplier
     *
     * @ORM\ManyToOne(targetEntity="\Boodmo\User\Entity\UserProfile\Supplier")
     * @ORM\JoinColumn(name="supplier_profile_id", referencedColumnName="id")
     */
    private $supplierProfile;

    /**
     * @var int
     *
     * @ORM\Column(name="delivery_days", type="smallint", precision=0, scale=0, nullable=false)
     */
    private $deliveryDays;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="\Boodmo\Sales\Entity\OrderItem",
     *     mappedBy="package", fetch="EXTRA_LAZY", cascade={"persist", "remove"})
     */
    private $items;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, precision=0, scale=0, nullable=true)
     */
    private $currency = MoneyService::BASE_CURRENCY;

    /**
     * @var string
     *
     * @ORM\Column(name="shipping_status", type="string", length=50, precision=0, scale=0, nullable=true)
     */
    private $shippingStatus;

    /**
     * @var string
     *
     * @ORM\Column(name="shipping_method", type="string", length=50, precision=0, scale=0, nullable=true)
     */
    private $shippingMethod;

    /**
     * @ORM\Column(name="shipping_eta", type="date", nullable=true)
     */
    private $shippingETA;

    /**
     * @ORM\Column(name="deliveredAt", type="date", nullable=true)
     */
    private $deliveredAt;

    /**
     * @var string
     *
     * @ORM\Column(name="track_number", type="string", length=100, precision=0, scale=0, nullable=true)
     */
    private $trackNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_number", type="string", length=255, precision=0, scale=0, nullable=true)
     */
    private $invoiceNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="facilitation_invoice_number", type="string", length=255, precision=0, scale=0, nullable=true)
     */
    private $facilitationInvoiceNumber;

    /**
     * @var array
     *
     * @ORM\Column(name="invoice_snapshot", type="json_array", precision=0, scale=0, nullable=false, unique=false,
     *     options={"default" = "{}"}, columnDefinition="JSONB DEFAULT '{}'::jsonb")
     */
    private $invoiceSnapshot = [];

    /**
     * @var array
     * @deprecated Remove after multicurrency release
     *
     * @ORM\Column(name="workflow_history", type="json_array", precision=0, scale=0, nullable=false, unique=false,
     *     options={"default" = "{}"}, columnDefinition="JSONB DEFAULT '{}'::jsonb NOT NULL")
     */
    protected $workflowHistory = [];

    /**
     * @var ShippingBox
     *
     * @ORM\ManyToOne(targetEntity="\Boodmo\Shipping\Entity\ShippingBox", inversedBy="packages", cascade={"persist"})
     * @ORM\JoinColumn(name="shipping_box", referencedColumnName="id", onDelete="SET NULL")
     */
    private $shippingBox;

    /**
     * @var string
     *
     * @ORM\Column(name="external_invoice", type="string", length=255, precision=0, scale=0, nullable=true)
     */
    private $externalInvoice;

    /**
     * OrderPackage constructor.
     */
    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

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
     * @return OrderBundle
     */
    public function getBundle(): ?OrderBundle
    {
        return $this->bundle;
    }

    public function getParent(): ?StatusProviderAggregateInterface
    {
        return $this->bundle;
    }

    /**
     * @param OrderBundle $bundle
     *
     * @return $this
     */
    public function setBundle(OrderBundle $bundle): self
    {
        $this->bundle = $bundle;
        return $this;
    }

    /**
     * @return Supplier
     * @throws \RuntimeException
     */
    public function getSupplierProfile(): ?Supplier
    {
        return $this->supplierProfile;
    }

    /**
     * @param $supplierProfile
     *
     * @return $this
     * @throws \RuntimeException
     */
    public function setSupplierProfile(Supplier $supplierProfile): self
    {
        if ($supplierProfile->getBaseCurrency() !== $this->getCurrency()) {
            throw new \RuntimeException(
                sprintf(
                    'Package (id: %s) & Supplier (id: %s) have different currency (%s != %s).',
                    $this->getId(),
                    $supplierProfile->getId(),
                    $this->getCurrency(),
                    $supplierProfile->getBaseCurrency()
                )
            );
        }
        $this->supplierProfile = $supplierProfile;
        return $this;
    }

    /**
     * @return int
     */
    public function getDeliveryDays(): int
    {
        return $this->deliveryDays ?? 1;
    }

    /**
     * @param int $deliveryDays
     *
     * @return $this
     */
    public function setDeliveryDays(int $deliveryDays): self
    {
        $this->deliveryDays = $deliveryDays;
        return $this;
    }

    public function getGrandTotal(): int
    {
        return $this->getSubTotal() + $this->getDeliveryTotal() - $this->getDiscountTotal();
    }

    public function getBaseGrandTotal(): int
    {
        return $this->getBaseSubTotal() + $this->getBaseDeliveryTotal() - $this->getBaseDiscountTotal();
    }

    /**
     * @deprecated Maybe not needed
     * @return int
     */
    public function getOriginalTotal(): int
    {
        $total = 0;
        foreach ($this->getItems() as $item) {
            $total += $item->getOriginPrice() * $item->getQty();
        }
        return $total;
    }

    /**
     * @deprecated Maybe not needed
     * @return int
     */
    public function getBaseOriginalPriceTotal(): int
    {
        $total = 0;
        foreach ($this->getItems() as $item) {
            $total += $item->getBaseOriginPrice() * $item->getQty();
        }
        return $total;
    }

    public function getCostTotal(): int
    {
        $total = 0;
        foreach ($this->getItems()->filter($this->getItemFilter()) as $item) {
            $total += $item->getCost() * $item->getQty();
        }
        return $total;
    }

    public function getBaseCostTotal(): int
    {
        $total = 0;
        foreach ($this->getItems()->filter($this->getItemFilter()) as $item) {
            $total += $item->getBaseCost() * $item->getQty();
        }
        return $total;
    }

    public function getDeliveryTotal(): int
    {
        $total = 0;
        foreach ($this->getItems()->filter($this->getItemFilter()) as $item) {
            $total += $item->getDeliveryTotal();
        }
        return $total;
    }

    public function getBaseDeliveryTotal(): int
    {
        $total = 0;
        foreach ($this->getItems()->filter($this->getItemFilter()) as $item) {
            $total += $item->getBaseDeliveryTotal();
        }
        return $total;
    }

    public function getSubTotal(): int
    {
        $total = 0;
        foreach ($this->getItems()->filter($this->getItemFilter()) as $item) {
            $total += $item->getSubTotal();
        }
        return $total;
    }

    public function getBaseSubTotal(): int
    {
        $total = 0;
        foreach ($this->getItems()->filter($this->getItemFilter()) as $item) {
            $total += $item->getBaseSubTotal();
        }
        return $total;
    }

    public function getDiscountTotal(): int
    {
        $total = 0;
        foreach ($this->getItems()->filter($this->getItemFilter()) as $item) {
            $total += $item->getDiscount();
        }
        return $total;
    }

    public function getBaseDiscountTotal(): int
    {
        $total = 0;
        foreach ($this->getItems()->filter($this->getItemFilter()) as $item) {
            $total += $item->getBaseDiscount();
        }
        return $total;
    }

    /**
     * @param bool $allowProxyCommission
     * @return int
     */
    public function getFacilitationFee(bool $allowProxyCommission = false): int
    {
        if ($allowProxyCommission
            and $supplier = $this->getSupplierProfile()
            and $supplier->getAccountingType() === Supplier::ACCOUNTING_TYPE_AGENT
            and $agent = $supplier->getAccountingAgent()
        ) {
            $result = round($this->getSubTotal() * $agent->getAccountingSelfData('commission', 0) / 100);
        } else {
            $result = $this->getSubTotal() - $this->getCostTotal();
        }
        return $result;
    }

    /**
     * @return OrderItem[]|ArrayCollection|Collection
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    /**
     * @return Collection|ArrayCollection|StatusProviderInterface[]
     */
    public function getChildren(): Collection
    {
        return $this->getItems();
    }

    /**
     * @param ArrayCollection|Collection $items
     * @return $this
     */
    public function setItems(Collection $items): self
    {
        $this->items = $items;
        return $this;
    }

    /**
     * @param OrderItem $item
     *
     * @param bool $recalculate
     * @return $this|OrderPackage
     */
    public function addItem(OrderItem $item, bool $recalculate = true): self
    {
        $item->setPackage($this);
        $this->items->add($item);
        $this->statusAggregate($this);
        if ($recalculate && $this->getBundle() !== null) {
            $this->getBundle()->recalculateBills();
        }
        return $this;
    }

    /**
     * @param OrderItem $item
     *
     * @param bool $recalculate
     * @return $this|OrderPackage
     */
    public function removeItem(OrderItem $item, bool $recalculate = true): self
    {
        $this->items->removeElement($item);
        $this->statusAggregate($this);
        if ($recalculate && $this->getBundle() !== null) {
            $this->getBundle()->recalculateBills();
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getFullNumber(): string
    {
        return $this->getBundle()->getNumber() . '-'.$this->getNumber();
    }

    /**
     * @return int | null
     */
    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function getPackageNumber(): string
    {
        return $this->getBundle()->getId() . '-' . $this->getNumber();
    }

    /**
     * @param int $number
     *
     * @return $this
     */
    public function setNumber(int $number): self
    {
        $this->number = $number;
        return $this;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    /**
     * @param string $invoiceNumber
     *
     * @return $this
     */
    public function setInvoiceNumber(string $invoiceNumber)
    {
        $this->invoiceNumber = $invoiceNumber;
        return $this;
    }

    public function getExternalInvoice(): ?string
    {
        return $this->externalInvoice;
    }

    /**
     * @param string $externalInvoice
     *
     * @return $this
     */
    public function setExternalInvoice(string $externalInvoice)
    {
        $this->externalInvoice = $externalInvoice;
        return $this;
    }

    /**
     * @return string
     */
    public function getFacilitationInvoiceNumber(): ?string
    {
        return $this->facilitationInvoiceNumber;
    }

    /**
     * @param string $facilitationInvoiceNumber
     *
     * @return $this
     */
    public function setFacilitationInvoiceNumber(string $facilitationInvoiceNumber): self
    {
        $this->facilitationInvoiceNumber = $facilitationInvoiceNumber;
        return $this;
    }

    /**
     * @return array
     */
    public function getInvoiceSnapshot(): array
    {
        return $this->invoiceSnapshot;
    }

    /**
     * @param array $invoiceSnapshot
     * @return OrderPackage
     */
    public function setInvoiceSnapshot(array $invoiceSnapshot): self
    {
        $this->invoiceSnapshot = $invoiceSnapshot;
        return $this;
    }

    public function getShippingETA(): ?DateTime
    {
        return $this->shippingETA;
    }

    public function setShippingETA(DateTime $eta): self
    {
        $oldEta = $this->getShippingETA() ? $this->getShippingETA()->format('d-m-Y') : null;
        $newEta = $eta->format('d-m-Y');
        if ($oldEta && ($oldEta !== $newEta)) {
            $noteMessage = "ETA is updated from $oldEta to $newEta";
            $message = new NotesMessage('SALES', $noteMessage);
            $this->addMessageToNotes($message);
        }

        $this->shippingETA = $eta;
        if ($shippingBox = $this->getShippingBox()) {
            $boxETA = $shippingBox->getShippingETA();
            if ($eta !== $boxETA) {
                $shippingBox->setShippingETA($eta);
            }
        }
        return $this;
    }

    public function getDeliveredAt(): ?DateTime
    {
        return $this->deliveredAt;
    }

    public function setDeliveredAt(DateTime $deliveredAt): self
    {
        $this->deliveredAt = $deliveredAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     *
     * @return $this
     */
    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * Cancel Item Filter
     * @return Closure
     */
    public function getItemFilter(): Closure
    {
        $allowCalcCancelled = $this->getBundle() ? $this->getBundle()->getSumCanceledItemsFlag() : false;
        return function (OrderItem $item) use ($allowCalcCancelled) {
            if ($allowCalcCancelled) {
                $result = !$item->isReplaced();
            } else {
                $result = !$item->isCancelled();
            }
            return $result;
        };
    }

    public function getSourceState(): string
    {
        /* @var Address|false $address */
        $address = $this->getSupplierProfile()->getAddresses()->filter(function (Address $address) {
            return $address->getType() === 'billing';
        })->first();
        return $address ? $address->getState() : self::DEFAULT_STATE;
    }

    public function getDestinationState(): string
    {
        $customerAddress = $this->bundle->getCustomerAddress();
        return $customerAddress['state'] ?? self::DEFAULT_STATE;
    }

    /**
     * @return OrderItem[]|Collection
     */
    public function getActiveItems(): Collection
    {
        $cancelled = StatusEnum::build(StatusEnum::CANCELLED);
        $cancelRequested = StatusEnum::build(StatusEnum::CANCEL_REQUESTED_USER);
        $cancelSupplier = StatusEnum::build(StatusEnum::CANCEL_REQUESTED_SUPPLIER);

        return $this->getItems()->filter(function (OrderItem $item) use (
            $cancelled,
            $cancelRequested,
            $cancelSupplier
        ) {
            $list = $item->getStatusList();
            return !$list->exists($cancelled) && !$list->exists($cancelRequested) && !$list->exists($cancelSupplier);
        });
    }

    /**
     * @return string
     */
    public function generateInvoiceNumber() : string
    {
        return sprintf(
            '%s/%s-%s',
            $this->getSupplierProfile()->getId(),
            $this->getBundle()->getId(),
            $this->getNumber()
        );
    }

    /**
     * @param bool $onlyActive
     * @return int
     */
    public function getItemsCount(bool $onlyActive = true): int
    {
        $itemsCount = 0;
        $items = $onlyActive ? $this->getActiveItems() : $this->getItems()->filter($this->getItemFilter());
        foreach ($items as $orderItem) {
            $itemsCount += $orderItem->getQty();
        }
        return $itemsCount;
    }

    /**
     * @return ShippingBox
     */
    public function getShippingBox(): ?ShippingBox
    {
        return $this->shippingBox;
    }

    /**
     * @param ShippingBox $shippingBox
     *
     * @return $this
     */
    public function setShippingBox(ShippingBox $shippingBox): self
    {
        $this->shippingBox = $shippingBox;
        return $this;
    }

    public function calculateShippingETA(): void
    {
        $dispatchItemDates = $this->getItems()->map(function (OrderItem $item) {
            return $item->getDispatchDate();
        })->toArray();
        if (!$dispatchItemDates) {
            return;
        }
        $dispatchDate = clone max($dispatchItemDates);
        $this->setShippingETA($dispatchDate->add(
            new \DateInterval('P' . $this->getDeliveryDays() . 'D')
        ));
    }

    public function getCustomerStatusName(): string
    {
        $allowShowStatus = $this->getBundle() && !empty($this->getBundle()->getStatusList()->get(Status::TYPE_GENERAL));
        $statusList = $this->getStatusList();
        return $statusList->count() && $allowShowStatus
            ? $statusList->fallbackStatus(Status::TYPE_CUSTOMER)->getName()
            : '';
    }

    public function __clone()
    {
        $this->items = new ArrayCollection();
        $this->id = null;
        $this->number = null;
        $this->shippingETA = null;
        $this->shippingBox = null;
        $this->deliveredAt = null;
    }
}
