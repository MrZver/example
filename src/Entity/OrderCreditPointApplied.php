<?php

namespace Boodmo\Sales\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Ramsey\Uuid\Uuid;

/**
 * Class OrderCreditPointApplied.
 *
 * @ORM\Table(name="sales_order_credit_point_applied")
 * @ORM\Entity(repositoryClass="Boodmo\Sales\Repository\OrderCreditPointAppliedRepository")
 */
class OrderCreditPointApplied
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
     * @var CreditPoint
     *
     * @ORM\ManyToOne(targetEntity="\Boodmo\Sales\Entity\CreditPoint", inversedBy="creditPointsApplied")
     * @ORM\JoinColumn(name="credit_point_id", referencedColumnName="id", nullable=false)
     */
    private $creditPoint;

    /**
     * @var OrderBill
     *
     * @ORM\ManyToOne(targetEntity="\Boodmo\Sales\Entity\OrderBill", inversedBy="creditPointsApplied")
     * @ORM\JoinColumn(name="order_bill_id", referencedColumnName="id", nullable=false)
     */
    private $bill;

    /**
     * @var int
     *
     * @ORM\Column(name="amount", type="integer")
     */
    private $amount = 0;

    public function __construct()
    {
        $this->id = (string) Uuid::uuid4();
    }

    /**
     * @param CreditPoint $creditPoint
     * @param OrderBill $bill
     * @param int $amount
     * @return $this|OrderCreditPointApplied
     * @throws \DomainException
     */
    public static function make(CreditPoint $creditPoint, OrderBill $bill, int $amount)
    {
        if ($creditPoint->getCurrency() !== $bill->getCurrency()) {
            throw new \DomainException(
                sprintf(
                    'Bill (id: %s) & CreditPoint (id: %s) have different currency (%s != %s).',
                    $bill->getId(),
                    $creditPoint->getId(),
                    $bill->getCurrency(),
                    $creditPoint->getCurrency()
                )
            );
        }
        return (new self())
            ->setCreditPoint($creditPoint)
            ->setBill($bill)
            ->setAmount($amount);
    }

    public function hash(): string
    {
        return md5($this->getCreditPoint()->getId() . $this->getBill()->getId());
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
     * @return CreditPoint
     */
    public function getCreditPoint(): CreditPoint
    {
        return $this->creditPoint;
    }

    /**
     * @param CreditPoint $creditPoint
     * @return $this
     */
    public function setCreditPoint(CreditPoint $creditPoint): self
    {
        $this->creditPoint = $creditPoint;
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
        $unused = $this->getCreditPoint()->getUnusedAmount();
        if ($unused < $amount) {
            throw new \DomainException(
                sprintf(
                    'Unused amount in creditPoint (id: %s) too small (%s < %s).',
                    $this->getCreditPoint()->getId(),
                    $unused,
                    $amount
                )
            );
        }
        $this->amount = $amount;
        return $this;
    }
}
