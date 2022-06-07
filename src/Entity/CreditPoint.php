<?php

namespace Boodmo\Sales\Entity;

use Boodmo\Currency\Service\MoneyService;
use Boodmo\User\Entity\UserProfile\Customer;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Money\Currency;
use Money\Money;
use Ramsey\Uuid\Uuid;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Class CreditPoint.
 *
 * @ORM\Table(name="sales_credit_points")
 * @ORM\Entity(repositoryClass="Boodmo\Sales\Repository\CreditPointRepository")
 */
class CreditPoint
{
    use TimestampableEntity;

    private const BASE_CURRENCY = MoneyService::BASE_CURRENCY;
    public const TYPE_PRICE_INCREASED_BY_SUPPLIER = 'Price increased by Supplier';
    public const TYPE_CLAIM_ACCEPTED = 'Claim accepted';
    public const TYPE_TRANSFER_OF_UNAPPLIED_PAYMENT = 'Transfer of unapplied payment';

    /**
     * @var Uuid
     *
     * @ORM\Column(name="id", type="guid", nullable=false)
     * @ORM\Id
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="total", type="integer")
     */
    private $total = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="base_total", type="integer", nullable=true)
     */
    private $baseTotal = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     */
    private $currency = MoneyService::BASE_CURRENCY;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255, nullable=true, unique=false)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="zohobooks_id", type="string", length=255, nullable=true, unique=false)
     */
    private $zohoBooksId;

    /**
     * Ordered By CustomerProfile
     *
     * @var Customer
     *
     * @ORM\ManyToOne(targetEntity="\Boodmo\User\Entity\UserProfile\Customer")
     * @ORM\JoinColumn(name="customer_profile_id", referencedColumnName="id")
     */
    private $customerProfile;

    /**
     * @var OrderCreditPointApplied
     *
     * @ORM\OneToMany(targetEntity="\Boodmo\Sales\Entity\OrderCreditPointApplied", mappedBy="creditPoint",
     *     fetch="EXTRA_LAZY", cascade={"persist"})
     */
    private $creditPointsApplied;

    /**
     * CreditPoint constructor.
     */
    public function __construct()
    {
        $this->id = (string) Uuid::uuid4();
        $this->creditPointsApplied = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
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
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @return Money
     */
    public function getTotalMoney(): Money
    {
        return new Money($this->getTotal(), new Currency($this->getCurrency()));
    }

    /**
     * @param int $total
     * @return $this
     */
    public function setTotal(int $total): self
    {
        $this->total = $total;
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
     * @return Money
     */
    public function getBaseTotalMoney(): Money
    {
        return new Money($this->getBaseTotal(), new Currency(self::BASE_CURRENCY));
    }

    /**
     * @param int $baseTotal
     *
     * @return $this
     */
    public function setBaseTotal(int $baseTotal): self
    {
        $this->baseTotal = $baseTotal;
        return $this;
    }

    /**
     * @return float
     */
    public function getCurrencyRate(): float
    {
        return $this->getTotal() ? $this->getBaseTotal() / $this->getTotal() : 0;
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
     * @return $this
     */
    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type ?? '';
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getZohoBooksId(): string
    {
        return $this->zohoBooksId ?? '';
    }

    /**
     * @param string $zohoBooksId
     * @return $this
     */
    public function setZohoBooksId(string $zohoBooksId): self
    {
        $this->zohoBooksId = $zohoBooksId;

        return $this;
    }

    /**
     * @return Customer|null
     */
    public function getCustomerProfile(): ?Customer
    {
        return $this->customerProfile;
    }

    /**
     * @param Customer $customerProfile
     *
     * @return $this
     */
    public function setCustomerProfile(Customer $customerProfile): self
    {
        $this->customerProfile = $customerProfile;
        return $this;
    }

    /**
     * @return OrderCreditPointApplied[]|ArrayCollection|Collection
     */
    public function getCreditPointsApplied(): Collection
    {
        return $this->creditPointsApplied;
    }

    /**
     * @return int
     */
    public function getUsedAmount(): int
    {
        $result = 0;
        foreach ($this->getCreditPointsApplied() as $creditPointApplied) {
            $result += $creditPointApplied->getAmount();
        }
        return $result;
    }

    /**
     * @return int
     */
    public function getUnusedAmount(): int
    {
        return $this->getTotal() - $this->getUsedAmount();
    }

    /**
     * @param OrderBill $bill
     * @param int $amount
     * @return $this
     * @throws \DomainException
     */
    public function applyToBill(OrderBill $bill, int $amount): self
    {
        $bill->addCreditPoint($this, $amount);
        return $this;
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function getBills(): Collection
    {
        $bills = new ArrayCollection();
        foreach ($this->getCreditPointsApplied() as $creditPointApplied) {
            $bill = $creditPointApplied->getBill();
            if (!$bills->contains($bill)) {
                $bills->add($bill);
            }
        }
        return $bills;
    }

    public function __clone()
    {
        $this->id = (string)Uuid::uuid4();
        $this->zohoBooksId = null;
        $this->creditPointsApplied = new ArrayCollection();
    }
}
