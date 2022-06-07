<?php

namespace Boodmo\Sales\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;
use Boodmo\User\Entity\User;

final class CancelApproveItemCommand extends AbstractCommand
{
    /** @var string */
    private $itemId;

    /** @var User */
    private $editor;

    /** @var int */
    private $reason;

    /** @var bool */
    private $locked;

    public function __construct(string $itemId, User $editor, int $reason, bool $locked)
    {
        parent::__construct();
        $this->itemId = $itemId;
        $this->editor = $editor;
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
    public function getLocked(): bool
    {
        return $this->locked;
    }
}
