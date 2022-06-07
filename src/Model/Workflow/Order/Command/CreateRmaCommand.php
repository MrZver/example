<?php

namespace Boodmo\Sales\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;
use Boodmo\User\Entity\User;

class CreateRmaCommand extends AbstractCommand
{
    /** @var string */
    private $itemId;

    /** @var  string */
    private $intent;

    /** @var  string */
    private $reason;

    /** @var   */
    private $note;

    /** @var int */
    private $qty;

    /** @var User */
    private $user;

    /**
     * CreateRmaCommand constructor.
     *
     * @param string $itemId
     * @param string $intent
     * @param string $reason
     * @param string $note
     * @param int $qty
     * @param User $user
     * @throws \RuntimeException
     */
    public function __construct(string $itemId, string $intent, string $reason, ?string $note, int $qty, User $user)
    {
        if ($qty <= 0) {
            throw new \RuntimeException(sprintf('Incorrect qty (%s) for return (item id: %s)', $qty, $itemId));
        }

        parent::__construct();
        $this->itemId = $itemId;
        $this->intent = $intent;
        $this->reason = $reason;
        $this->note   = $note;
        $this->qty    = $qty;
        $this->user   = $user;
    }

    /**
     * @return string
     */
    public function getItemId(): string
    {
        return $this->itemId;
    }

    /**
     * @return string
     */
    public function getIntent(): string
    {
        return $this->intent;
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * @return mixed
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @return int
     */
    public function getQty(): int
    {
        return $this->qty;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }
}
