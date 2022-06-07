<?php

namespace Boodmo\Sales\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;

class AskForCourierCommand extends AbstractCommand
{
    /** @var int */
    private $packageId;

    public function __construct(int $packageId)
    {
        parent::__construct();
        $this->packageId = $packageId;
    }

    /**
     * @return int
     */
    public function getPackageId(): int
    {
        return $this->packageId;
    }
}
