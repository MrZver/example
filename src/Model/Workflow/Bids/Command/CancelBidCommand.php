<?php

namespace Boodmo\Sales\Model\Workflow\Bids\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;
use Boodmo\User\Entity\UserProfile\Supplier;

class CancelBidCommand extends AbstractCommand
{
    /** @var string */
    private $bidId;

    /** @var Supplier */
//    private $supplier;

    /**
     * CancelBidCommand constructor.
     *
     * @param string $bidId
     */
    public function __construct(string $bidId/*, Supplier $supplier*/)
    {
        parent::__construct();
        $this->bidId = $bidId;
        /*$this->supplier = $supplier;*/
    }

    /**
     * @return string
     */
    public function getBidId(): string
    {
        return $this->bidId;
    }

    /**
     * @return Supplier
     */
    /*public function getSupplier(): Supplier
    {
        return $this->supplier;
    }*/
}
