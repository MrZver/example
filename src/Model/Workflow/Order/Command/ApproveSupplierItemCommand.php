<?php

namespace Boodmo\Sales\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;
use Boodmo\Sales\Model\Workflow\Order\Handler\ApproveSupplierItemHandler;
use Boodmo\User\Entity\User;

final class ApproveSupplierItemCommand extends AbstractCommand
{
    protected const HANDLER = ApproveSupplierItemHandler::class;
    /**
     * @var string
     */
    private $itemId;
    /**
     * @var User
     */
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
