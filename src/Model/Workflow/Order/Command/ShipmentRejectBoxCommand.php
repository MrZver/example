<?php

namespace Boodmo\Sales\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;
use Boodmo\Sales\Model\Workflow\Order\Handler\ShipmentRejectBoxHandler;
use Boodmo\User\Entity\User;

final class ShipmentRejectBoxCommand extends AbstractCommand
{
    protected const HANDLER = ShipmentRejectBoxHandler::class;

    /** @var string */
    private $shippingBoxId;

    /** @var User */
    private $editor;

    /** @var int */
    private $reason;

    public function __construct(string $shippingBoxId, User $editor, int $reason)
    {
        parent::__construct();
        $this->shippingBoxId = $shippingBoxId;
        $this->editor = $editor;
        $this->reason = $reason;
    }

    /**
     * @return string
     */
    public function getShippingBoxId(): string
    {
        return $this->shippingBoxId;
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
}
