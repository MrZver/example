<?php

namespace Boodmo\Sales\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;
use Boodmo\Sales\Model\Workflow\Order\Handler\ShipmentDeniedPackageHandler;
use Boodmo\User\Entity\User;

final class ShipmentDeniedPackageCommand extends AbstractCommand
{
    protected const HANDLER = ShipmentDeniedPackageHandler::class;

    /** @var string */
    private $packId;

    /** @var User */
    private $editor;

    public function __construct(int $packId, User $editor)
    {
        parent::__construct();
        $this->packId = $packId;
        $this->editor = $editor;
    }

    /**
     * @return string
     */
    public function getPackageId(): string
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
