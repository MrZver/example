<?php

namespace Boodmo\Sales\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;
use Boodmo\Sales\Model\Workflow\Order\Handler\ShipmentRequestSendBoxHandler;
use Boodmo\User\Entity\User;
use DateTime;

final class ShipmentRequestSendBoxCommand extends AbstractCommand
{
    protected const HANDLER = ShipmentRequestSendBoxHandler::class;

    /** @var string */
    private $shippingBoxId;

    /** @var User */
    private $editor;

    /** @var string */
    private $shipper;

    /** @var string */
    private $trackNum;

    /** @var int */
    private $cashAmount;

    /**
     * @var string
     */
    private $shippingETA;

    public function __construct(
        string $shippingBoxId,
        User $editor,
        string $shipper,
        string $trackNum,
        string $shippingETA = null,
        int $cashAmount = null
    ) {
        parent::__construct();
        $this->shippingBoxId = $shippingBoxId;
        $this->editor = $editor;
        $this->shipper = $shipper;
        $this->trackNum = $trackNum;
        $this->shippingETA = ($shippingETA) ? new DateTime($shippingETA) : null;
        $this->cashAmount = $cashAmount;
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
     * @return string
     */
    public function getShipper() : string
    {
        return $this->shipper;
    }

    /**
     * @return string
     */
    public function getTrackNum() : string
    {
        return $this->trackNum;
    }

    /**
     * @return int
     */
    public function getCashAmount(): ?int
    {
        return $this->cashAmount;
    }

    /**
     * @return DateTime
     */
    public function getShippingETA(): ?DateTime
    {
        return $this->shippingETA;
    }
}
