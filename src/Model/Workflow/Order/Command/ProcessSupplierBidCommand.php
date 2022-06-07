<?php

namespace Boodmo\Sales\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;
use Boodmo\User\Entity\User;

class ProcessSupplierBidCommand extends AbstractCommand
{
    /** @var string */
    private $bidId;

    /** @var float */
    private $price;

    /** @var float */
    private $cost;

    /** @var int */
    private $supplier;

    /** @var string */
    private $itemId;

    /** @var \DateTime */
    private $dispatchDate;

    /** @var string */
    private $brand;

    /** @var string */
    private $number;

    /** @var int */
    private $gst;

    /** @var array */
    private $notes;

    /** @var string */
    private $handler;

    /** @var User */
    private $editor;

    public function __construct(
        string $itemId,
        int $supplier,
        float $price,
        float $cost,
        \DateTime $dispatchDate,
        User $editor,
        ?string $bidId = null,
        ?string $brand = null,
        ?string $number = null,
        ?int $gst = null,
        ?array $notes = null,
        ?string $handler = null
    ) {
        parent::__construct();
        $this->price = $price;
        $this->cost = $cost;
        $this->supplier = $supplier;
        $this->bidId = $bidId;
        $this->itemId = $itemId;
        $this->dispatchDate = $dispatchDate;
        $this->editor = $editor;
        $this->brand = $brand;
        $this->number = $number;
        $this->gst = $gst;
        $this->notes = $notes;
        $this->handler = $handler;
    }

    /**
     * @return string
     */
    public function getBidId() : ?string
    {
        return $this->bidId;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @return float
     */
    public function getCost(): float
    {
        return $this->cost;
    }

    /**
     * @return int
     */
    public function getSupplier(): int
    {
        return $this->supplier;
    }

    /**
     * @return string
     */
    public function getItemId(): string
    {
        return $this->itemId;
    }

    /**
     * @return \DateTime
     */
    public function getDispatchDate(): \DateTime
    {
        return $this->dispatchDate;
    }

    /**
     * @return string
     */
    public function getBrand(): ?string
    {
        return $this->brand;
    }

    /**
     * @return string
     */
    public function getNumber(): ?string
    {
        return $this->number;
    }

    /**
     * @return int
     */
    public function getGst(): ?int
    {
        return $this->gst;
    }

    /**
     * @return array
     */
    public function getNotes(): ?array
    {
        return $this->notes;
    }

    /**
     * @return string
     */
    public function getHandler(): ?string
    {
        return $this->handler;
    }

    /**
     * @return User
     */
    public function getEditor(): User
    {
        return $this->editor;
    }
}
