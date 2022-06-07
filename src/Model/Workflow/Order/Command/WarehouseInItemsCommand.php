<?php

namespace Boodmo\Sales\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;
use Boodmo\Sales\Model\Workflow\Order\Handler\WarehouseInItemsHandler;
use Boodmo\User\Entity\User;

class WarehouseInItemsCommand extends AbstractCommand
{
    protected const HANDLER = WarehouseInItemsHandler::class;
    /**
     * @var array
     */
    private $items;
    /**
     * @var User
     */
    private $editor;

    public function __construct(array $items, User $editor)
    {
        parent::__construct();
        $this->items = $items;
        $this->editor = $editor;
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
    public function getAcceptedList(): array
    {
        return array_column($this->items, 'accepted', 'id');
    }

    /**
     * @return User
     */
    public function getEditor(): User
    {
        return $this->editor;
    }
}
