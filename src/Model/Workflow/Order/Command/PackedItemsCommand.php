<?php

namespace Boodmo\Sales\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;
use Boodmo\Sales\Model\Workflow\Order\Handler\PackedItemsHandler;
use Boodmo\User\Entity\User;

class PackedItemsCommand extends AbstractCommand
{
    protected const HANDLER = PackedItemsHandler::class;

    /**
     * @var array
     */
    private $items;
    /**
     * @var User
     */
    private $editor;

    /**
     * @var array
     */
    private $shipmentParams;

    /**
     * @var string
     */
    private $shippingBoxId;

    public function __construct(array $items, array $shipmentParams, User $editor, string $shippingBoxId)
    {
        parent::__construct();
        $this->items = $items;
        $this->shipmentParams = $shipmentParams;
        $this->editor = $editor;
        $this->shippingBoxId = $shippingBoxId;
    }

    /**
     * @return array
     */
    public function getItemsIds(): array
    {
        return array_column($this->items, 'id');
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return array_column($this->items, null, 'id');
    }

    /**
     * @return User
     */
    public function getEditor(): User
    {
        return $this->editor;
    }

    /**
     * @return array
     */
    public function getShipmentParams(): array
    {
        return $this->shipmentParams;
    }

    /**
     * @return string
     */
    public function getShippingBoxId(): string
    {
        return $this->shippingBoxId;
    }
}
