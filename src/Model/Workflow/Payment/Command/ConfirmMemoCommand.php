<?php

namespace Boodmo\Sales\Model\Workflow\Payment\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;
use Boodmo\User\Entity\User;

class ConfirmMemoCommand extends AbstractCommand
{
    /**
     * @var int
     */
    private $bundleId;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var float
     */
    private $refund;

    /**
     * @var float
     */
    private $leave;

    /**
     * AddBillCommand constructor.
     * @param int $bundleId
     * @param string $currency
     * @param float $refund
     * @param float $leave
     */
    public function __construct(int $bundleId, string $currency, float $refund, float $leave)
    {
        parent::__construct();
        $this->bundleId = $bundleId;
        $this->currency = $currency;
        $this->refund = $refund;
        $this->leave = $leave;
    }

    /**
     * @return int
     */
    public function getBundleId(): int
    {
        return $this->bundleId;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @return float
     */
    public function getRefund(): float
    {
        return $this->refund;
    }

    /**
     * @return float
     */
    public function getLeave(): float
    {
        return $this->leave;
    }
}
