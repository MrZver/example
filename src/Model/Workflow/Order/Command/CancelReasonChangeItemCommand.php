<?php

namespace Boodmo\Sales\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;

final class CancelReasonChangeItemCommand extends AbstractCommand
{
    /** @var string */
    private $itemId;

    /** @var int */
    private $reason;

    /** @var bool */
    private $locked;

    public function __construct(string $itemId, int $reason, bool $locked)
    {
        parent::__construct();
        $this->itemId = $itemId;
        $this->reason = $reason;
        $this->locked = $locked;
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
    public function getReason(): int
    {
        return $this->reason;
    }

    /**
     * @return bool
     */
    public function getLocked() : bool
    {
        return $this->locked;
    }
}
