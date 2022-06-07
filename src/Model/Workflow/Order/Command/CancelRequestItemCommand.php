<?php

namespace Boodmo\Sales\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;
use Boodmo\User\Entity\User;

final class CancelRequestItemCommand extends AbstractCommand
{
    /** @var string */
    private $itemId;

    /** @var User */
    private $editor;

    /** @var int */
    private $reason;

    /**
     * @var bool
     */
    private $isCustomer;

    public function __construct(string $itemId, User $editor, int $reason, $isCustomer = false)
    {
        parent::__construct();
        $this->itemId = $itemId;
        $this->editor = $editor;
        $this->reason = $reason;
        $this->isCustomer = $isCustomer;
    }

    /**
     * @return string
     */
    public function getItemId(): string
    {
        return $this->itemId;
    }

    /**
     * @return User
     */
    public function getEditor(): User
    {
        return $this->editor;
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
    public function isCustomer(): bool
    {
        return $this->isCustomer;
    }
}
