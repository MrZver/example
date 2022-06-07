<?php

namespace Boodmo\Sales\Entity;

use Boodmo\Currency\Service\MoneyService;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Money\Currency;
use Money\Money;
use Ramsey\Uuid\Uuid;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Class OrderBill.
 *
 * @ORM\Table(name="sales_order_bills")
 * @ORM\Entity(repositoryClass="Boodmo\Sales\Repository\OrderBillRepository")
 */
class OrderBill
{
    use TimestampableEntity;

    private const BASE_CURRENCY = MoneyService::BASE_CURRENCY;

    public const TYPE_POSTPAID = 'postpaid';
    public const TYPE_PREPAID = 'prepaid';
    public const TYPE_ON_DELIVERY = 'on_delivery';

    public const STATUS_PAID = 'PAID';
    public const STATUS_OPEN = 'OPEN';
    public const STATUS_PARTIALLY_PAID = 'PARTIALLY_PAID';
    public const STATUS_OVERDUE = 'OVERDUE';

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
     * @ORM\ManyToOne(targetEntity="\Boodmo\Sales\Entity\OrderBundle", inversedBy="bills")
     * @ORM\JoinColumn(name="order_bundle_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $bundle;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_method", type="string", length=100, nullable=true, unique=false)
     */
    private $paymentMethod;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=50, nullable=true, unique=false)
     */
    private $type;

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
     * @var array
     *
     * @ORM\Column(name="history", type="json_array", nullable=false, unique=false,
     *     options={"default" = "{}"}, columnDefinition="JSONB DEFAULT '{}'::jsonb NOT NULL")
     */
    private $history = [];

    /**
     * @var OrderPaymentApplied
     *
     * @ORM\OneToMany(targetEntity="\Boodmo\Sales\Entity\OrderPaymentApplied", mappedBy="bill", fetch="EXTRA_LAZY",
     *     cascade={"persist"})
     */
    private $paymentsApplied;

    /**
     * @var OrderCreditPointApplied
     *
     * @ORM\OneToMany(targetEntity="\Boodmo\Sales\Entity\OrderCreditPointApplied", mappedBy="bill", fetch="EXTRA_LAZY",
     *     cascade={"persist"})
     */
    private $creditPointsApplied;

    /**
     * OrderBill constructor.
     */
    public function __construct()
    {
        $this->id = (string) Uuid::uuid4();
        $this->paymentsApplied = new ArrayCollection();
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
     * @return OrderBundle
     */
    public function getBundle(): ?OrderBundle
    {
        return $this->bundle;
    }

    /**
     * @param OrderBundle $bundle
     *
     * @return $this
     */
    public function setBundle(?OrderBundle $bundle): self
    {
        $this->bundle = $bundle;
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
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType(string $type): self
    {
        $this->type = $type;
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
     * @return $this
     */
    public function setBaseTotal(int $baseTotal): self
    {
        $this->baseTotal = $baseTotal;
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
     * @return array
     */
    public function getHistory(): array
    {
        return $this->history ?? [];
    }

    /**
     * @param array $history
     * @return $this
     */
    public function setHistory(array $history): self
    {
        $this->history = $history;
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
     * @param OrderPaymentApplied $paymentApplied
     * @return $this
     */
    public function removePaymentApplied(OrderPaymentApplied $paymentApplied): self
    {
        $this->paymentsApplied->removeElement($paymentApplied);
        return $this;
    }

    /**
     * @param Payment $payment
     * @param int $amount
     * @return $this
     * @throws \DomainException
     */
    public function addPayment(Payment $payment, int $amount): self
    {
        OrderPaymentApplied::make($payment, $this, $amount);

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
     * @param OrderCreditPointApplied $creditPointApplied
     * @return $this
     */
    public function removeCreditPointApplied(OrderCreditPointApplied $creditPointApplied): self
    {
        $this->creditPointsApplied->removeElement($creditPointApplied);
        return $this;
    }

    /**
     * @param CreditPoint $creditPoint
     * @param int $amount
     * @return $this
     * @throws \DomainException
     */
    public function addCreditPoint(CreditPoint $creditPoint, int $amount): self
    {
        /* @var OrderCreditPointApplied $creditPointApplied */
        $creditPointsApplied = [];
        foreach ($this->getCreditPointsApplied() as $linked) {
            $creditPointsApplied[$linked->hash()] = $linked;
        }
        $creditPointApplied = OrderCreditPointApplied::make($creditPoint, $this, $amount);
        $hash = $creditPointApplied->hash();
        if (isset($creditPointsApplied[$hash])) {
            $creditPointApplied = $creditPointsApplied[$hash];
            $creditPointApplied->setAmount($creditPointApplied->getAmount() + $amount);
        } else {
            $this->creditPointsApplied->add($creditPointApplied);
        }
        return $this;
    }

    /**
     * @return int
     */
    public function getPaidAmount(): int
    {
        return $this->getPaymentsAmount() + $this->getCreditPointsAmount();
    }

    /**
     * @return int
     */
    public function getPaymentsAmount(): int
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
    public function getCreditPointsAmount(): int
    {
        $result = 0;
        foreach ($this->getCreditPointsApplied() as $creditPointApplied) {
            $result += $creditPointApplied->getAmount();
        }
        return $result;
    }

    public function getPaymentDue(): int
    {
        return $this->getTotal() - $this->getPaidAmount();
    }

    public function getStatus(): string
    {
        $paidAmount = $this->getPaidAmount();
        switch ($this->getTotal() <=> $paidAmount) {
            case 0:
                return self::STATUS_PAID;
            case -1:
                return self::STATUS_OVERDUE;
        }
        return $paidAmount ? self::STATUS_PARTIALLY_PAID : self::STATUS_OPEN;
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function getPayments(): Collection
    {
        $payments = new ArrayCollection();
        foreach ($this->getPaymentsApplied() as $paymentApplied) {
            $payment = $paymentApplied->getPayment();
            if (!$payments->contains($payment)) {
                $payments->add($payment);
            }
        }
        return $payments;
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function getCreditPoints(): Collection
    {
        $creditPoints = new ArrayCollection();
        foreach ($this->getCreditPointsApplied() as $creditPointApplied) {
            $creditPoint = $creditPointApplied->getCreditPoint();
            if (!$creditPoints->contains($creditPoint)) {
                $creditPoints->add($creditPoint);
            }
        }
        return $creditPoints;
    }
}
