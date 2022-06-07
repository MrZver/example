<?php

namespace Boodmo\Sales\Entity;

use Boodmo\Sales\Model\Workflow\Note\NotesableEntityIntarface;
use Boodmo\Sales\Model\Workflow\EntityStatusTrait;
use Boodmo\Sales\Model\Workflow\Note\NotesableEntityTrait;
use Boodmo\Sales\Model\Workflow\Status\StatusProviderAggregateInterface;
use Boodmo\Sales\Model\Workflow\Status\StatusProviderInterface;
use Boodmo\User\Entity\UserProfile\Customer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Money\Currency;
use Money\Money;

/**
 * Class OrderBundle.
 *
 * @ORM\Table(name="sales_order")
 * @ORM\Entity(repositoryClass="Boodmo\Sales\Repository\OrderBundleRepository")
 */
class OrderBundle implements StatusProviderAggregateInterface, NotesableEntityIntarface
{
    use TimestampableEntity, EntityStatusTrait, NotesableEntityTrait;

    public const DEFAULT_AFFILIATE = 'web';

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
     * @var string
     *
     * @ORM\Column(name="customer_email", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $customerEmail;

    /**
     * @var array
     *
     * @ORM\Column(name="customer_address", type="json_array", precision=0, scale=0, nullable=false, unique=false,
     *     options={"default" = "{}"}, columnDefinition="JSONB DEFAULT '{}'::jsonb NOT NULL")
     */
    private $customerAddress = [];

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
     * @var string
     *
     * @ORM\Column(name="payment_method", type="string", length=100, precision=0, scale=0, nullable=false, unique=false)
     */
    private $paymentMethod = '';

    /**
     * @var bool
     *
     * @ORM\Column(name="checkout_as_guest", type="boolean", precision=0, scale=0, nullable=false, unique=false,
     *     options={"default" = true})
     */
    private $checkoutAsGuest = true;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="\Boodmo\Sales\Entity\OrderPackage",
     *     mappedBy="bundle", fetch="EXTRA_LAZY", cascade={"persist", "remove"})
     */
    private $packages;

    /**
     * @var string
     *
     * @ORM\Column(name="client_ip", type="string", length=100, precision=0, scale=0, nullable=true, unique=false)
     */
    private $clientIp;

    /**
     * @var string
     *
     * @ORM\Column(name="ga_cid", type="string", length=30, precision=0, scale=0, nullable=true, unique=false)
     */
    private $gaCid;

    /**
     * @var string
     *
     * @ORM\Column(name="affiliate", type="string", length=50, precision=0, scale=0, nullable=true, unique=false)
     */
    private $affiliate = self::DEFAULT_AFFILIATE;

    /**
     * @var string
     *
     * @ORM\Column(name="currency_rate", type="float", precision=10, scale=3, nullable=true, unique=false)
     */
    private $currencyRate;

    /**
     * @var CreditMemo
     *
     * @ORM\OneToMany(targetEntity="\Boodmo\Sales\Entity\CreditMemo", mappedBy="bundle", fetch="EXTRA_LAZY",
     *     cascade={"persist", "remove"})
     */
    private $creditmemos;

    /**
     * @var OrderBill
     *
     * @ORM\OneToMany(targetEntity="\Boodmo\Sales\Entity\OrderBill", mappedBy="bundle", fetch="EXTRA_LAZY",
     *     cascade={"persist", "remove"})
     */
    private $bills;

    private $sumCanceledItemsFlag = false;

    /**
     * OrderBundle constructor.
     */
    public function __construct()
    {
        $this->packages = new ArrayCollection();
        $this->creditmemos = new ArrayCollection();
        $this->bills = new ArrayCollection();
    }

    /**
     * Get id.
     *
     * @return int|null
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
     * @return $this|OrderBundle
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @todo refactor this via OrderNumber Model
     * @return string
     */
    public function getNumber(): string
    {
        $date = $this->getCreatedAt();
        $control = ((int)$date->format('d') + (int)$date->format('m') + $this->getId()) % 10;
        return $date->format('dm/').$control.sprintf('%05d', $this->getId());
    }

    /**
     * @return string
     */
    public function getCustomerEmail(): string
    {
        return $this->customerEmail ?? '';
    }

    /**
     * @param string $customerEmail
     *
     * @return $this|OrderBundle
     */
    public function setCustomerEmail(string $customerEmail): self
    {
        $this->customerEmail = $customerEmail;
        return $this;
    }

    /**
     * @return array
     */
    public function getCustomerAddress(): array
    {
        return $this->customerAddress ?? [];
    }

    /**
     * @param array $customerAddress
     *
     * @return $this|OrderBundle
     */
    public function setCustomerAddress(array $customerAddress): self
    {
        $this->customerAddress = $customerAddress;
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
     * @return $this|OrderBundle
     */
    public function setCustomerProfile(Customer $customerProfile): self
    {
        $this->customerProfile = $customerProfile;
        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    /**
     * @return string[]
     */
    public function getPaymentMethods(): array
    {
        return explode(',', $this->paymentMethod);
    }

    /**
     * @param string $paymentMethod
     *
     * @return $this|OrderBundle
     */
    public function setPaymentMethod(string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getCheckoutAsGuest(): bool
    {
        return $this->isCheckoutAsGuest();
    }

    /**
     * @return boolean
     */
    public function isCheckoutAsGuest(): bool
    {
        return $this->checkoutAsGuest;
    }

    /**
     * @param boolean $checkoutAsGuest
     *
     * @return $this|OrderBundle
     */
    public function setCheckoutAsGuest(bool $checkoutAsGuest): self
    {
        $this->checkoutAsGuest = $checkoutAsGuest;
        return $this;
    }

    /**
     * @return int
     */
    public function getItemsCount(): int
    {
        $items = 0;
        $this->packages->forAll(function (int $key, OrderPackage $package) use (&$items) {
            foreach ($package->getItems() as $orderItem) {
                $items += $orderItem->getQty();
            };
            return true;
        });
        return $items;
    }

    /**
     * @param string $currency
     *
     * @return int
     */
    public function getDeliveryTotal(string $currency): int
    {
        return array_reduce($this->packages->toArray(), $this->getPackageSumBy(__FUNCTION__, $currency), 0);
    }

    /**
     * @return int
     */
    public function getBaseDeliveryTotal(): int
    {
        return array_reduce($this->packages->toArray(), $this->getPackageSumBy(__FUNCTION__), 0);
    }

    public function getDiscountTotal(string $currency): int
    {
        return array_reduce($this->packages->toArray(), $this->getPackageSumBy(__FUNCTION__, $currency), 0);
    }

    public function getBaseDiscountTotal(): int
    {
        return array_reduce($this->packages->toArray(), $this->getPackageSumBy(__FUNCTION__), 0);
    }

    public function getSubTotal(string $currency): int
    {
        return array_reduce($this->packages->toArray(), $this->getPackageSumBy(__FUNCTION__, $currency), 0);
    }

    public function getBaseSubTotal(): int
    {
        return array_reduce($this->packages->toArray(), $this->getPackageSumBy(__FUNCTION__), 0);
    }

    /**
     * @param string $currency
     * @return int
     */
    public function getGrandTotal(string $currency): int
    {
        return array_reduce($this->packages->toArray(), $this->getPackageSumBy(__FUNCTION__, $currency), 0);
    }

    public function getBaseGrandTotal(): int
    {
        return array_reduce($this->packages->toArray(), $this->getPackageSumBy(__FUNCTION__), 0);
    }

    protected function getPackageSumBy(string $totalMethod, string $currency = null): \Closure
    {
        return function (int $total, OrderPackage $package) use ($totalMethod, $currency) {
            if ($currency !== null && $package->getCurrency() !== $currency) {
                return $total + 0;
            }
            return $total + $package->$totalMethod();
        };
    }

    /**
     * @return OrderPackage[]|ArrayCollection|Collection
     */
    public function getPackages(): Collection
    {
        return $this->packages;
    }

    /**
     * @return Collection|ArrayCollection|StatusProviderInterface[]
     */
    public function getChildren(): Collection
    {
        return $this->getPackages();
    }

    /**
     * @param string $currency
     *
     * @return OrderPackage[]|ArrayCollection|Collection
     */
    public function getPackagesWithCurrency(string $currency): Collection
    {
        return $this->packages->filter(function (OrderPackage $package) use ($currency) {
            return $package->getCurrency() === $currency;
        });
    }

    /**
     * @param OrderPackage $package
     *
     * @return $this
     */
    public function addPackage(OrderPackage $package): self
    {
        $package->setNumber(empty($package->getNumber()) ? $this->packages->count() + 1 : $package->getNumber());
        $package->setBundle($this);
        $this->packages->add($package);
        $this->recalculateBills();
        return $this;
    }

    /**
     * @param ArrayCollection $packages
     *
     * @return $this|OrderBundle
     */
    public function setPackages(ArrayCollection $packages): self
    {
        $this->packages = $packages;
        return $this;
    }

    /**
     * @return string
     */
    public function getClientIp(): string
    {
        return $this->clientIp ?? '';
    }

    /**
     * @param string $clientIp
     *
     * @return $this|OrderBundle
     */
    public function setClientIp(string $clientIp): self
    {
        $this->clientIp = $clientIp;
        return $this;
    }

    /**
     * @return string
     */
    public function getGaCid(): string
    {
        return $this->gaCid ?? '';
    }

    /**
     * @param string $gaCid
     *
     * @return $this|OrderBundle
     */
    public function setGaCid(string $gaCid): self
    {
        $this->gaCid = $gaCid;
        return $this;
    }

    /**
     * @param OrderBill $orderBill
     *
     * @return $this|OrderBundle
     */
    public function addBill(OrderBill $orderBill): self
    {
        $orderBill->setBundle($this);
        $this->bills->add($orderBill);
        return $this;
    }

    /**
     * @return OrderBill[]|Collection
     */
    public function getPrepaidBills(): Collection
    {
        return $this->getBills()->filter(
            function (OrderBill $orderBill) {
                return $orderBill->getType() === OrderBill::TYPE_PREPAID;
            }
        );
    }

    /**
     * @param array $type
     * @return OrderBill[]|Collection
     */
    public function getUnpaidBills(array $type = [OrderBill::TYPE_PREPAID]): Collection
    {
        return $this->getBills()->filter(
            function (OrderBill $orderBill) use ($type) {
                $isCorrectStatus = in_array(
                    $orderBill->getStatus(),
                    [OrderBill::STATUS_OPEN, OrderBill::STATUS_PARTIALLY_PAID],
                    true
                );
                $isCorrectType = $type ? in_array($orderBill->getType(), $type, true) : true;
                return $isCorrectStatus && $isCorrectType;
            }
        );
    }

    /**
     * @return OrderBill[]|Collection
     */
    public function getPaidBills(): Collection
    {
        return $this->getBills()->filter(
            function (OrderBill $orderBill) {
                $status = $orderBill->getStatus();
                return $status === OrderBill::STATUS_PAID || $status === OrderBill::STATUS_OVERDUE;
            }
        );
    }

    /**
     * @param CreditMemo $creditmemo
     *
     * @return OrderBundle
     */
    public function addCreditMemo(CreditMemo $creditmemo): self
    {
        $creditmemo->setBundle($this);
        $this->creditmemos->add($creditmemo);
        return $this;
    }

    /**
     * @param Collection $creditmemos
     *
     * @return OrderBundle
     *
     */
    public function setCreditMemos(Collection $creditmemos): self
    {
        $this->creditmemos = $creditmemos;
        return $this;
    }

    /**
     * @return CreditMemo[]|ArrayCollection|Collection
     */
    public function getCreditMemos(): Collection
    {
        return $this->creditmemos;
    }

    /**
     * @return string
     */
    public function getAffiliate(): string
    {
        return $this->affiliate ?? '';
    }

    /**
     * @param string $affiliate
     *
     * @return $this|OrderBundle
     */
    public function setAffiliate(string $affiliate): self
    {
        $this->affiliate = $affiliate;
        return $this;
    }

    /**
     * @return float
     */
    public function getCurrencyRate(): float
    {
        return (float) $this->currencyRate;
    }

    /**
     * @param float $currencyRate
     *
     * @return $this|OrderBundle
     */
    public function setCurrencyRate(float $currencyRate): self
    {
        $this->currencyRate = $currencyRate;

        return $this;
    }

    public function getParent(): ?StatusProviderAggregateInterface
    {
        return null;
    }

    /**
     * @return Money[]
     */
    public function getGrandTotalList() : array
    {
        $totals = [];

        foreach ($this->getPackages() as $package) {
            $currency = $package->getCurrency();
            $totals[$currency] = new Money($this->getGrandTotal($currency), new Currency($currency));
        }

        $totals && ksort($totals);

        return $totals;
    }

    /**
     * @return Money[]
     */
    public function getSubTotalList() : array
    {
        $totals = [];

        foreach ($this->getPackages() as $package) {
            $currency = $package->getCurrency();
            $totals[$currency] = new Money($this->getSubTotal($currency), new Currency($currency));
        }

        $totals && ksort($totals);

        return $totals;
    }

    /**
     * @return Money[]
     */
    public function getDeliveryTotalList() : array
    {
        $totals = [];

        foreach ($this->getPackages() as $package) {
            $currency = $package->getCurrency();
            $totals[$currency] = new Money($this->getDeliveryTotal($currency), new Currency($currency));
        }

        $totals && ksort($totals);

        return $totals;
    }

    /**
     * @param Collection $bills
     * @return $this
     *
     */
    public function setBills(Collection $bills): self
    {
        $this->bills = $bills;
        return $this;
    }

    /**
     * @return OrderBill[]|ArrayCollection|Collection
     */
    public function getBills(): Collection
    {
        return $this->bills;
    }

    /**
     * Total of all bills
     * @return Money[]|array - ['INR' => Money(), 'USD' => Money(), ...]
     */
    public function getBillsTotalList(): array
    {
        $totals = [];
        foreach ($this->getBills() as $bill) {
            $currency = $bill->getCurrency();
            $total = new Money($bill->getTotal(), new Currency($currency));
            $totals[$currency] = isset($totals[$currency]) ? $totals[$currency]->add($total) : $total;
        }
        $totals && ksort($totals);
        return $totals;
    }

    /**
     * Total of all OrderPaymentApplied
     * @return Money[]|array - ['INR' => Money(), 'USD' => Money(), ...]
     */
    public function getPaymentsAppliedMoney(): array
    {
        $totals = [];
        foreach ($this->getBills() as $bill) {
            $currency = $bill->getCurrency();
            $amount = $bill->getPaymentsAmount();
            if (!empty($amount)) {
                $total = new Money($amount, new Currency($currency));
                $totals[$currency] = isset($totals[$currency]) ? $totals[$currency]->add($total) : $total;
            }
        }
        $totals && ksort($totals);
        return $totals;
    }

    public function getPaymentsMoney(): array
    {
        $processedPayments = [];
        $totals = [];
        foreach ($this->getBills() as $bill) {
            $currency = $bill->getCurrency();
            foreach ($bill->getPaymentsApplied() as $paymentApplied) {
                $payment = $paymentApplied->getPayment();
                if (!isset($processedPayments[$payment->getId()])) {
                    $processedPayments[$payment->getId()] = $payment;
                    $total = new Money($payment->getTotal(), new Currency($currency));
                    $totals[$currency] = isset($totals[$currency]) ? $totals[$currency]->add($total) : $total;
                }
            }
        }
        $totals && ksort($totals);
        return $totals;
    }

    /**
     * Total of all OrderPackage
     * @return Money[]|array - ['INR' => Money(), 'USD' => Money(), ...]
     */
    public function getPackagesMoney(): array
    {
        $totals = [];
        foreach ($this->getPackages() as $package) {
            $currency = $package->getCurrency();
            $amount = $package->getGrandTotal();
            if (!empty($amount)) {
                $total = new Money($amount, new Currency($currency));
                $totals[$currency] = isset($totals[$currency]) ? $totals[$currency]->add($total) : $total;
            }
        }
        $totals && ksort($totals);
        return $totals;
    }

    /**
     * Total of all Credit Memos
     * @return Money[]|array - ['INR' => Money(), 'USD' => Money(), ...]
     */
    public function getRefundsMoney(): array
    {
        $totals = [];
        foreach ($this->getCreditMemos() as $memo) {
            $currency = $memo->getCurrency();
            $total = $memo->getTotalMoney();
            if (!empty($total)) {
                $totals[$currency] = isset($totals[$currency]) ? $totals[$currency]->add($total) : $total;
            }
        }
        $totals && ksort($totals);
        return $totals;
    }

    /**
     * Total of all OrderCreditPointApplied
     * @return Money[]|array - ['INR' => Money(), 'USD' => Money(), ...]
     */
    public function getCreditPointsAppliedMoney(): array
    {
        $totals = [];
        foreach ($this->getBills() as $bill) {
            $currency = $bill->getCurrency();
            $amount = $bill->getCreditPointsAmount();
            if (!empty($amount)) {
                $total = new Money($amount, new Currency($currency));
                $totals[$currency] = isset($totals[$currency]) ? $totals[$currency]->add($total) : $total;
            }
        }
        $totals && ksort($totals);
        return $totals;
    }

    /**
     * Total of all OrderPaymentApplied + OrderCreditPointApplied
     * @return Money[]|array - ['INR' => Money(), 'USD' => Money(), ...]
     */
    public function getPaidMoney(): array
    {
        $totals = [];

        foreach ($this->getPaymentsAppliedMoney() as $currency => $paidMoney) {
            $totals[$currency] = isset($totals[$currency]) ? $totals[$currency]->add($paidMoney) : $paidMoney;
        }
        foreach ($this->getCreditPointsAppliedMoney() as $currency => $paidMoney) {
            $totals[$currency] = isset($totals[$currency]) ? $totals[$currency]->add($paidMoney) : $paidMoney;
        }

        $totals && ksort($totals);

        return $totals;
    }

    public function hasPaymentsDue(string $currency): bool
    {
        $result = false;
        $totalsToPay = $this->getGrandTotalList();
        if (!empty($totalsToPay[$currency]) and !$totalsToPay[$currency]->isZero()) {
            $totalsPaid = $this->getPaidMoney();
            if (!isset($totalsPaid[$currency]) || $totalsToPay[$currency]->greaterThan($totalsPaid[$currency])) {
                $result = true;
            }
        }
        return $result;
    }

    public function recalculateBills(): void
    {
        /* @var OrderBill $bill */
        $totalsToPay = $this->getGrandTotalList();
        $billsData = [];
        foreach ($this->getBills() as $bill) {
            $currency = $bill->getCurrency();
            if (!isset($billsData[$currency])) {
                $billsData[$currency] = ['total' => 0, 'baseTotal' => 0, 'bills' => []];
            }
            $billsData[$currency]['total'] += $bill->getTotal();
            $billsData[$currency]['baseTotal'] += $bill->getBaseTotal();
            $billsData[$currency]['bills'][$bill->getId()] = $bill;
        }

        foreach ($billsData as $currency => $billData) {
            $needPayTotal = isset($totalsToPay[$currency]) ? (int)$totalsToPay[$currency]->getAmount() : 0;
            $needPayBaseTotal = 0;
            foreach ($this->getPackagesWithCurrency($currency) as $package) {
                $needPayBaseTotal += $package->getBaseGrandTotal();
            }

            if ($billData['total'] > $needPayTotal) {
                $diff = $billData['total'] - $needPayTotal;
                $diffBase =  $billData['baseTotal'] - $needPayBaseTotal;
                foreach ($billData['bills'] as $bill) {
                    $billTotal = $bill->getTotal();
                    $billTotalBase = $bill->getBaseTotal();
                    if ($billTotal > $diff) {
                        $bill->setTotal($billTotal - $diff)->setBaseTotal($billTotalBase - $diffBase);
                        $diff = 0;
                    } else {
                        $bill->setTotal(0)->setBaseTotal(0);
                        $diff -= $billTotal;
                    }

                    if (empty($diff)) {
                        break;
                    }
                }
            } elseif ($billData['total'] < $needPayTotal) {
                $bill = array_values($billData['bills'])[0];
                $bill->setTotal($needPayTotal)->setBaseTotal($needPayBaseTotal);
            }
        }
    }

    public function setSumCanceledItemsFlag(bool $value): self
    {
        $this->sumCanceledItemsFlag = $value;
        return $this;
    }

    public function getSumCanceledItemsFlag(): bool
    {
        return $this->sumCanceledItemsFlag;
    }
}
