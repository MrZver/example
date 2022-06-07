<?php

namespace Boodmo\Sales\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Ramsey\Uuid\Uuid;

/**
 * Class OrderPaymentApplied.
 *
 * @ORM\Table(name="sales_order_payments_applied")
 * @ORM\Entity(repositoryClass="Boodmo\Sales\Repository\OrderPaymentAppliedRepository")
 */
class OrderPaymentApplied
{
    use TimestampableEntity;

    /**
     * @var Uuid
     *
     * @ORM\Column(name="id", type="guid", nullable=false)
     * @ORM\Id
     */
    private $id;

    /**
     * @var Payment
     *
     * @ORM\ManyToOne(targetEntity="\Boodmo\Sales\Entity\Payment", inversedBy="paymentsApplied")
     * @ORM\JoinColumn(name="payment_id", referencedColumnName="id", nullable=false)
     */
    private $payment;

    /**
     * @var OrderBill
     *
     * @ORM\ManyToOne(targetEntity="\Boodmo\Sales\Entity\OrderBill", inversedBy="paymentsApplied")
     * @ORM\JoinColumn(name="order_bill_id", referencedColumnName="id", nullable=false)
     */
    private $bill;

    /**
     * @var int
     *
     * @ORM\Column(name="amount", type="integer")
     */
    private $amount = 0;

    /**
     * OrderPaymentApplied constructor.
     */
    private function __construct()
    {
        $this->id = (string) Uuid::uuid4();
    }

    /**
     * @param Payment $payment
     * @param OrderBill $bill
     * @param int $amount
     * @return $this|OrderPaymentApplied
     * @throws \DomainException
     */
    public static function make(Payment $payment, OrderBill $bill, int $amount)
    {
        if ($payment->getCurrency() !== $bill->getCurrency()) {
            throw new \DomainException(
                sprintf(
                    'Bill (id: %s) & Payment (id: %s) have different currency (%s != %s).',
                    $bill->getId(),
                    $payment->getId(),
                    $bill->getCurrency(),
                    $payment->getCurrency()
                )
            );
        }
        $applied = (new self())
                    ->setPayment($payment)
                    ->setBill($bill);
        /* @var OrderPaymentApplied $paymentApplied */
        $existApplied = false;
        foreach (array_merge(
            $bill->getPaymentsApplied()->toArray(),
            $payment->getPaymentsApplied()->toArray()
        ) as $linked) {
            if ($linked->hash() === $applied->hash()) {
                $applied = $linked;
                $existApplied = true;
            }
        }
        if (!$existApplied) {
            $bill->getPaymentsApplied()->add($applied);
            $payment->getPaymentsApplied()->add($applied);
        }
        $applied->setAmount($applied->getAmount() + $amount);
        return $applied;
    }

    public function hash(): string
    {
        return md5($this->getPayment()->getId() . $this->getBill()->getId());
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
     * @return Payment
     */
    public function getPayment(): Payment
    {
        return $this->payment;
    }

    /**
     * @param Payment $payment
     * @return $this
     */
    public function setPayment(Payment $payment): self
    {
        $this->payment = $payment;
        return $this;
    }

    /**
     * @return OrderBill
     */
    public function getBill(): OrderBill
    {
        return $this->bill;
    }

    /**
     * @param OrderBill $bill
     * @return $this
     */
    public function setBill(OrderBill $bill): self
    {
        $this->bill = $bill;
        return $this;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     * @return $this
     * @throws \DomainException
     */
    public function setAmount(int $amount): self
    {
        $beforeUnused = $this->getPayment()->getUnusedAmount();
        $this->amount = $amount;
        $unused = $this->getPayment()->getUnusedAmount();
        if ($unused < 0) {
            throw new \DomainException(
                sprintf(
                    'Unused amount (%s) in payment (id: %s, total: %s) too small for apply %s.',
                    $beforeUnused,
                    $this->getPayment()->getId(),
                    $this->getPayment()->getTotal(),
                    $beforeUnused - $unused
                )
            );
        }

        return $this;
    }
}
