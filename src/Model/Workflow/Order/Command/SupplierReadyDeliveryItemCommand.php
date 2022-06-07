<?php

namespace Boodmo\Sales\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;
use Boodmo\Sales\Model\Workflow\Order\Handler\SupplierReadyDeliveryItemHandler;
use Boodmo\User\Entity\User;

final class SupplierReadyDeliveryItemCommand extends AbstractCommand
{
    protected const HANDLER = SupplierReadyDeliveryItemHandler::class;

    /** @var string */
    private $itemId;

    /** @var User */
    private $editor;

    public function __construct(string $itemId, User $editor)
    {
        parent::__construct();
        $this->itemId = $itemId;
        $this->editor = $editor;
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
}
