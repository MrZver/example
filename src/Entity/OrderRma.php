<?php

namespace Boodmo\Sales\Entity;

use Boodmo\Sales\Model\Workflow\Note\NotesableEntityIntarface;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Boodmo\Sales\Model\Workflow\Note\NotesableEntityTrait;
use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class OrderRma
 *
 * @ORM\Table(name="sales_order_rma")
 * @ORM\Entity(repositoryClass="Boodmo\Sales\Repository\OrderRmaRepository")
 */
class OrderRma implements NotesableEntityIntarface
{
    use TimestampableEntity, NotesableEntityTrait;

    public const STATUS_REQUESTED   = 'Requested';
    public const STATUS_IN_PROGRESS = 'In progress';
    public const STATUS_COMPLETED   = 'Completed';
    public const STATUS_REJECTED    = 'Rejected';

    public const BROKEN_PACKAGE         = 'BROKEN_PACKAGE';
    public const INACCURATE_DESCRIPTION = 'INACCURATE_DESCRIPTION';
    public const ITEM_DEFECTIVE         = 'ITEM_DEFECTIVE';
    public const ITEM_LATE              = 'ITEM_LATE';
    public const BUY_MISTAKE            = 'BUY_MISTAKE';
    public const WRONG_SENT             = 'WRONG_SENT';
    public const WRONG_RECEIVED         = 'WRONG_RECEIVED';
    public const NO_NEEDED              = 'NO_NEEDED';

    public const MONEY_RETURN      = 'MONEY_RETURN';
    public const REPLACE_WITH_PART = 'REPLACE_WITH_PART';

    public const STATUSES = [
        OrderRma::STATUS_REQUESTED,
        OrderRma::STATUS_IN_PROGRESS,
        OrderRma::STATUS_COMPLETED,
        OrderRma::STATUS_REJECTED,
    ];

    public const STATUSES_TRANSITIONS = [
        self::STATUS_REQUESTED => [
            self::STATUS_IN_PROGRESS,
            self::STATUS_REJECTED,
        ],
        self::STATUS_IN_PROGRESS => [
            self::STATUS_COMPLETED,
            self::STATUS_REJECTED,
        ],
        self::STATUS_COMPLETED => [
            self::STATUS_IN_PROGRESS,
        ],
        self::STATUS_REJECTED => [
            self::STATUS_IN_PROGRESS,
        ],
    ];

    public const REASONS = [
        self::BROKEN_PACKAGE         => ['name' => 'Products and shipping box are damaged'],
        self::INACCURATE_DESCRIPTION => ['name' => 'Inaccurate description'],
        self::ITEM_DEFECTIVE         => ['name' => 'Item defective or doesn\'t work'],
        self::ITEM_LATE              => ['name' => 'Item arrived too late'],
        self::BUY_MISTAKE            => ['name' => 'Bought by mistake'],
        self::WRONG_SENT             => ['name' => 'Wrong item was sent'],
        self::WRONG_RECEIVED         => ['name' => 'Received extra item I didn\'t buy (no refund needed)'],
        self::NO_NEEDED              => ['name' => 'No longer needed'],
    ];

    public const INTENTS = [
        self::MONEY_RETURN      => ['name' => 'Money return'],
        self::REPLACE_WITH_PART => ['name' => 'Replace with other part'],
    ];

    public const ALLOWED_COUNT_DAYS_FOR_RETURN = 10;

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
     * @ORM\Column(name="number", type="string", length=255, nullable=true, unique=false)
     */
    private $number;

    /**
     * @var int
     *
     * @ORM\Column(name="qty", type="integer")
     */
    private $qty = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="intent", type="string", length=255, nullable=true, unique=false)
     */
    private $intent;

    /**
     * @var string
     *
     * @ORM\Column(name="reason", type="string", length=255, nullable=true, unique=false)
     */
    private $reason;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255, nullable=true, unique=false)
     */
    private $status = self::STATUS_REQUESTED;

    /**
     * @var OrderItem
     *
     * @ORM\ManyToOne(targetEntity="\Boodmo\Sales\Entity\OrderItem", inversedBy="rmaList")
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id", nullable=false)
     */
    private $orderItem;

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
    public function getNumber(): string
    {
        return $this->number ?? '';
    }

    /**
     * @param string $number
     *
     * @return $this
     */
    public function setNumber(string $number): self
    {
        $this->number = $number;
        return $this;
    }

    /**
     * @return int
     */
    public function getQty(): int
    {
        return $this->qty ?? 0;
    }

    /**
     * @param int $qty
     *
     * @return $this
     */
    public function setQty(int $qty): self
    {
        $this->qty = $qty;
        return $this;
    }

    /**
     * @return string
     */
    public function getIntent(): string
    {
        return $this->intent ?? '';
    }

    /**
     * @param string $intent
     *
     * @return $this
     */
    public function setIntent(string $intent): self
    {
        $this->intent = $intent;
        return $this;
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason ?? '';
    }

    /**
     * @param string $reason
     *
     * @return $this
     */
    public function setReason(string $reason): self
    {
        $this->reason = $reason;
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
     * generate number
     *
     * @return string
     */
    public function generateNumber(): string
    {
        $bundle = $this->getOrderItem()->getPackage()->getBundle();
        $bundleRmaQty = 1;
        foreach ($bundle->getPackages() as $package) {
            foreach ($package->getItems() as $item) {
                $bundleRmaQty += count($item->getRmaList());
            }
        }
        return $bundle->getId() . "/" . $bundleRmaQty;
    }
}
