<?php

namespace Boodmo\Sales\Model\Workflow\Payment\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;

class NewCreditMemoCommand extends AbstractCommand
{
    /**
     * @var int
     */
    private $bundleId;

    /**
     * @var float
     */
    private $total;

    /**
     * @var float
     */
    private $calculatedTotal;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var string
     */
    private $open;

    /**
     * AddBillCommand constructor.
     * @param int $bundleId
     * @param float $total
     * @param float $calculatedTotal
     * @param string $currency
     * @param bool $open
     */
    public function __construct(int $bundleId, float $total, float $calculatedTotal, string $currency, bool $open)
    {
        parent::__construct();
        $this->bundleId = $bundleId;
        $this->total = $total;
        $this->calculatedTotal = $calculatedTotal;
        $this->currency = $currency;
        $this->open = $open;
    }

    /**
     * @return int
     */
    public function getBundleId(): int
    {
        return $this->bundleId;
    }

    /**
     * @return float
     */
    public function getTotal(): float
    {
        return $this->total;
    }

    /**
     * @return float
     */
    public function getCalculatedTotal(): float
    {
        return $this->calculatedTotal;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @return bool
     */
    public function getOpen(): bool
    {
        return $this->open;
    }
}
