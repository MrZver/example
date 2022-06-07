<?php

namespace Boodmo\Sales\Model\Workflow\Bids\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;
use Boodmo\User\Entity\UserProfile\Supplier;

class MissAskCommand extends AbstractCommand
{
    /** @var string */
    private $itemId;

    /** @var int */
    private $supplierId;

    /**
     * MissAskCommand constructor.
     *
     * @param string $itemId
     * @param int $supplierId
     */
    public function __construct(string $itemId, int $supplierId)
    {
        parent::__construct();
        $this->itemId = $itemId;
        $this->supplierId = $supplierId;
    }

    /**
     * @return string
     */
    public function getItemId(): string
    {
        return $this->itemId;
    }

    /**
     * @return int
     */
    public function getSupplierId(): int
    {
        return $this->supplierId;
    }
}
