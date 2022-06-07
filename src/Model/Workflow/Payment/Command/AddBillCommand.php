<?php

namespace Boodmo\Sales\Model\Workflow\Payment\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;

class AddBillCommand extends AbstractCommand
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
     * @var string
     */
    private $currency;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $type;

    /**
     * AddBillCommand constructor.
     * @param int $bundleId
     * @param float $total
     * @param string $currency
     * @param string $method
     * @param string $type
     */
    public function __construct(int $bundleId, float $total, string $currency, string $method, string $type)
    {
        parent::__construct();
        $this->bundleId = $bundleId;
        $this->total = $total;
        $this->currency = $currency;
        $this->method = $method;
        $this->type = $type;
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
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }
}
