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
 * Class Payment.
 *
 * @ORM\Table(name="sales_order_payments")
 * @ORM\Entity(repositoryClass="Boodmo\Sales\Repository\PaymentRepository")
 */
class Payment
{
    private const BASE_CURRENCY = MoneyService::BASE_CURRENCY;
    use TimestampableEntity;
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
     * @ORM\Column(name="transaction_id", type="string", length=255, nullable=true, unique=true)
     */
    private $transactionId;

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
     * @ORM\Column(name="zohobooks_id", type="string", length=255, nullable=true, unique=false)
     */
    private $zohoBooksId;


    /**
     * @var array
     *
     * @ORM\Column(name="history_trans", type="json_array", nullable=false, unique=false,
     *     options={"default" = "{}"}, columnDefinition="JSONB DEFAULT '{}'::jsonb NOT NULL")
     */
    private $historyTrans = [];

    /**
     * @var string
     *
     * @ORM\Column(name="payment_method", type="string", length=100, nullable=true, unique=false)
     */
    private $paymentMethod;

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
     * @var OrderPaymentApplied
     *
     * @ORM\OneToMany(targetEntity="\Boodmo\Sales\Entity\OrderPaymentApplied", mappedBy="payment", fetch="EXTRA_LAZY",
     *     cascade={"persist"})
     */
    private $paymentsApplied;
    
    /**
     * Payment constructor.
     */
    public function __construct()
    {
        $this->id = (string) Uuid::uuid4();
        $this->paymentsApplied = new ArrayCollection();
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
     * @return string
     */
    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    /**
     * @param $transactionId
     * @return $this
     */
    public function setTransactionId(?string $transactionId): self
    {
        $this->transactionId = $transactionId;

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
     * @param float $total
     *
     * @return $this
     */
    public function setTotal(int $total): self
    {
        $this->total = $total;

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
     *
     * @return $this
     */
    public function setZohoBooksId(string $zohoBooksId): self
    {
        $this->zohoBooksId = $zohoBooksId;

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
     * @return array
     */
    public function getHistoryTrans(): array
    {
        return $this->historyTrans ?? [];
    }

    /**
     * @param array $historyTrans
     * @return $this
     */
    public function setHistoryTrans(array $historyTrans): self
    {
        $this->historyTrans = $historyTrans;
        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    /**
     * @param string $paymentMethod
     *
     * @return $this
     */
    public function setPaymentMethod(string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;
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
     * @return $this
     */
    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
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
     * @return OrderPaymentApplied[]|ArrayCollection|Collection
     */
    public function getPaymentsApplied(): Collection
    {
        return $this->paymentsApplied;
    }

    /**
     * @return int
     */
    public function getUsedAmount(): int
    {
        $result = 0;
        foreach ($this->getPaymentsApplied() as $paymentApplied) {
            $result += $paymentApplied->getAmount();
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
     * @throws \RuntimeException
     */
    public function applyToBill(OrderBill $bill, int $amount): self
    {
        $bill->addPayment($this, $amount);
        return $this;
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function getBills(): Collection
    {
        $bills = new ArrayCollection();
        foreach ($this->getPaymentsApplied() as $paymentApplied) {
            $bill = $paymentApplied->getBill();
            if (!$bills->contains($bill)) {
                $bills->add($bill);
            }
        }
        return $bills;
    }

    public function __clone()
    {
        $this->transactionId = null;
        $this->historyTrans = [];
        $this->zohoBooksId = null;
        $this->id = (string)Uuid::uuid4();
        $this->paymentsApplied = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string)$this->id;
    }
}
