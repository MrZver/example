<?php

namespace Boodmo\Sales\Entity;

use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Model\Workflow\Note\NotesableEntityIntarface;
use Boodmo\Sales\Model\Workflow\Note\NotesableEntityTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Money\Currency;
use Money\Money;
use Ramsey\Uuid\Uuid;

/**
 * Class CreditMemo.
 *
 * @ORM\Table(name="sales_credit_memos")
 * @ORM\Entity(repositoryClass="Boodmo\Sales\Repository\CreditMemoRepository")
 */
class CreditMemo implements NotesableEntityIntarface
{
    use TimestampableEntity, NotesableEntityTrait;

    private const BASE_CURRENCY = MoneyService::BASE_CURRENCY;

    /**
     * @var Uuid
     *
     * @ORM\Column(name="id", type="guid", nullable=false)
     * @ORM\Id
     */
    private $id;

    /**
     * @var OrderBundle
     *
     * @ORM\ManyToOne(targetEntity="\Boodmo\Sales\Entity\OrderBundle", inversedBy="creditmemos", cascade={"persist"})
     * @ORM\JoinColumn(name="order_bundle_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $bundle;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_open", type="boolean", precision=0, scale=0, nullable=false, unique=false, options={"default" = true})
     */
    private $open = true;

    /**
     * @var integer
     *
     * @ORM\Column(name="total", type="integer", precision=10, scale=3, nullable=false, unique=false)
     */
    private $total = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="base_total", type="integer", precision=0, scale=0, nullable=true)
     */
    private $baseTotal = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, precision=0, scale=0, nullable=true)
     */
    private $currency;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="closed", type="datetime", precision=0, scale=0, nullable=true)
     */
    private $closed;

    /**
     * @var integer
     *
     * @ORM\Column(name="calculated_total", type="integer", precision=10, scale=3, nullable=true)
     */
    private $calculatedTotal = 0;

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
     * @return OrderBundle
     */
    public function getBundle(): ?OrderBundle
    {
        return $this->bundle;
    }

    /**
     * @param OrderBundle $bundle
     * @return CreditMemo
     */
    public function setBundle(OrderBundle $bundle): self
    {
        $this->bundle = $bundle;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isOpen(): bool
    {
        return $this->open;
    }

    /**
     * @param boolean $open
     * @return CreditMemo
     */
    public function setOpen(bool $open): self
    {
        $this->open = $open;
        if (!$open) {
            $this->setClosed(new DateTime());
        }

        return $this;
    }

    /**
     * @return integer
     */
    public function getTotal(): int
    {
        return $this->total ?? 0;
    }

    /**
     * @return Money
     */
    public function getTotalMoney(): Money
    {
        return new Money($this->getTotal(), new Currency($this->getCurrency()));
    }

    /**
     * @param integer $total
     * @return CreditMemo
     */
    public function setTotal(int $total): self
    {
        $this->total = $total;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getClosed() : ?DateTime
    {
        return $this->closed;
    }

    /**
     * @param DateTime $closed
     *
     * @return $this|CreditMemo
     */
    public function setClosed(DateTime $closed): self
    {
        $this->closed = $closed;

        return $this;
    }

    /**
     * @return int
     */
    public function getBaseTotal(): int
    {
        return $this->baseTotal ?? 0;
    }

    /**
     * @return int
     */
    public function getCalculatedTotal(): int
    {
        return $this->calculatedTotal ?? 0;
    }

    /**
     * @return Money
     */
    public function getBaseTotalMoney(): Money
    {
        return new Money($this->getBaseTotal(), new Currency(self::BASE_CURRENCY));
    }

    /**
     * @param int $baseTotal
     *
     * @return $this|CreditMemo
     */
    public function setBaseTotal(int $baseTotal): self
    {
        $this->baseTotal = $baseTotal;
        return $this;
    }

    /**
     * @param int $calculatedTotal
     *
     * @return $this|CreditMemo
     */
    public function setCalculatedTotal(int $calculatedTotal): self
    {
        $this->calculatedTotal = $calculatedTotal;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency ?? '';
    }

    /**
     * @param string $currency
     *
     * @return $this|CreditMemo
     */
    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function isHasCreditPoints(): bool
    {
        return $this->getBundle()
            and $creditPointsMoney = $this->getBundle()->getCreditPointsAppliedMoney()
            and isset($creditPointsMoney[$this->getCurrency()]);
    }
}
