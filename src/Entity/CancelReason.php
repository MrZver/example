<?php

namespace Boodmo\Sales\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class CancelReason.
 *
 * @ORM\Table(name="cancel_reason")
 * @ORM\Entity(repositoryClass="Boodmo\Sales\Repository\CancelReasonRepository")
 */
class CancelReason
{
    /**
     * Cancel reasons constants.
     * Must be equal with cancel_reason table
     */
    public const CUSTOMER_PRICE_CHANGED    = 1;
    public const CUSTOMER_DELIVERY_CHANGED = 2;
    public const CUSTOMER_CHANGED_MIND     = 3;
    public const CUSTOMER_NOT_REACHABLE    = 4;
    public const CANT_DELIVER              = 5;
    public const SUPPLIER_NO_STOCK         = 6;
    public const OTHER                     = 7;
    public const CUSTOMER_NO_VENDORS       = 8;
    public const DUPLICATE                 = 9;
    public const TEST                      = 10;
    public const ITEM_WAS_REPLACED         = 11;
    public const NO_COD_AVAILABLE          = 12;
    public const CUSTOM_CANCELLED_HIMSELF  = 13;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\SequenceGenerator(sequenceName="cancel_reason_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=200, precision=0, scale=0, nullable=false, unique=false)
     */
    private $name = '';

    /**
     * @var int
     *
     * @ORM\Column(name="sort", type="integer", precision=0, scale=0, nullable=true)
     */
    private $sort = 0;

    /**
     * @var bool
     *
     * @ORM\Column(name="custom", type="boolean", precision=0, scale=0, nullable=false, unique=false,
     *     options={"default" = false})
     */
    private $custom = false;

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
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
     * @return string
     */
    public function getName(): string
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
     * @deprecated Not needed anymore
     * @return int
     */
    public function getSort(): int
    {
        return $this->sort;
    }

    /**
     * @deprecated Not needed anymore
     * @param int $sort
     *
     * @return $this
     */
    public function setSort(int $sort): self
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * @return bool
     */
    public function getCustom(): bool
    {
        return $this->custom;
    }

    /**
     * @param $custom
     *
     * @return $this
     */
    public function setCustom(bool $custom): self
    {
        $this->custom = $custom;

        return $this;
    }
}
