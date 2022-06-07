<?php

namespace Boodmo\Sales\Model\Workflow\Order\Command;

use Boodmo\User\Entity\User;

class AddItemCommand extends EditItemCommand
{
    /** @var int */
    private $partId;

    public function __construct(int $packId, User $editor, int $price, int $cost, int $delivery, int $qty, int $partId)
    {
        parent::__construct($packId, $editor, $price, $cost, $delivery, $qty);
        $this->partId = $partId;
    }

    /**
     * @return int
     */
    public function getPartId(): int
    {
        return $this->partId;
    }
}
