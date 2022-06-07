<?php


namespace Boodmo\Sales\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;
use Boodmo\Sales\Model\Workflow\Order\Handler\ShipmentReturnPackageHandler;
use Boodmo\User\Entity\User;

class ShipmentReturnPackageCommand extends AbstractCommand
{
    protected const HANDLER = ShipmentReturnPackageHandler::class;

    /** @var string */
    private $packId;

    /** @var User */
    private $editor;

    public function __construct(string $packId, User $editor)
    {
        parent::__construct();
        $this->packId = $packId;
        $this->editor = $editor;
    }

    /**
     * @return string
     */
    public function getPackId(): string
    {
        return $this->packId;
    }

    /**
     * @return User
     */
    public function getEditor(): User
    {
        return $this->editor;
    }
}
