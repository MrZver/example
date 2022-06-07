<?php

namespace Boodmo\Sales\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;
use Boodmo\Sales\Model\Workflow\Order\Handler\SupplierRejectBoxHandler;
use Boodmo\User\Entity\User;

final class SupplierRejectBoxCommand extends AbstractCommand
{
    protected const HANDLER = SupplierRejectBoxHandler::class;

    /** @var string */
    private $shippingBoxId;

    /** @var User */
    private $editor;

    public function __construct(string $shippingBoxId, User $editor)
    {
        parent::__construct();
        $this->shippingBoxId = $shippingBoxId;
        $this->editor = $editor;
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
}
