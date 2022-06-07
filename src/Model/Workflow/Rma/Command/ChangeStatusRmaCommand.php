<?php

namespace Boodmo\Sales\Model\Workflow\Rma\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;

class ChangeStatusRmaCommand extends AbstractCommand
{
    /** @var string */
    private $id;

    /** @var string */
    private $status;

    /**
     * ChangeStatusRmaCommand constructor.
     *
     * @param string $id
     * @param string $status
     */
    public function __construct(string $id, string $status)
    {
        parent::__construct();
        $this->id = $id;
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }
}
