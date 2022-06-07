<?php


namespace Boodmo\Sales\Model\Workflow\Order\Command;

use Boodmo\User\Entity\User;

class SplitItemCommand extends ReplaceItemCommand
{
    /**
     * @var int
     */
    private $supplierProfileId;

    private $isNew;

    private $isDisable;

    private $bidId;

    public function __construct(
        int $supplierProfileId,
        string $itemId,
        User $editor,
        int $price,
        int $cost,
        int $delivery,
        int $qty,
        ?bool $isDisable,
        ?int $isNew,
        ?int $partId,
        ?string $bidId
    ) {
        parent::__construct($itemId, $editor, $price, $cost, $delivery, $qty, $partId);
        $this->supplierProfileId = $supplierProfileId;
        $this->isNew = $isNew;
        $this->isDisable = $isDisable;
        $this->bidId = $bidId;
    }

    /**
     * @return int
     */
    public function getSupplierProfileId(): int
    {
        return $this->supplierProfileId;
    }

    /**
     * @return int|null
     */
    public function getIsNew() : ?int
    {
        return $this->isNew;
    }

    /**
     * @return bool
     */
    public function isDisable(): bool
    {
        return $this->isDisable;
    }

    /**
     * @return null|string
     */
    public function getBidId()
    {
        return $this->bidId;
    }
}
