<?php

namespace Boodmo\Sales\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;
use Boodmo\Sales\Model\Workflow\Order\Handler\EditItemHandler;
use Boodmo\User\Entity\User;

class EditItemCommand extends AbstractCommand
{
    protected const HANDLER = EditItemHandler::class;

    /** @var string */
    private $itemId;

    /** @var User */
    private $editor;
    /**
     * @var int
     */
    private $price;
    /**
     * @var int
     */
    private $cost;
    /**
     * @var int
     */
    private $delivery;

    /** @var int */
    private $qty;

    public function __construct(string $itemId, User $editor, int $price, int $cost, int $delivery, int $qty)
    {
        parent::__construct();
        $this->itemId = $itemId;
        $this->editor = $editor;
        $this->price = $price;
        $this->cost = $cost;
        $this->delivery = $delivery;
        $this->qty = $qty;
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
    public function getPrice(): int
    {
        return $this->price;
    }

    /**
     * @return int
     */
    public function getCost(): int
    {
        return $this->cost;
    }

    /**
     * @return int
     */
    public function getDelivery(): int
    {
        return $this->delivery;
    }

    /**
     * @return int
     */
    public function getQty() : int
    {
        return $this->qty;
    }
}
