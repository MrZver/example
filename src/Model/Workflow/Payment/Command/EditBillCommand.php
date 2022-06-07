<?php

namespace Boodmo\Sales\Model\Workflow\Payment\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;

class EditBillCommand extends AbstractCommand
{
    /**
     * @var string
     */
    private $billId;

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
     * @param string $billId
     * @param float $total
     * @param string $currency
     * @param string $method
     * @param string $type
     * @internal param int $bundleId
     */
    public function __construct(string $billId, float $total, string $currency, string $method, string $type)
    {
        parent::__construct();
        $this->billId = $billId;
        $this->total = $total;
        $this->currency = $currency;
        $this->method = $method;
        $this->type = $type;
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

    /**
     * @return string
     */
    public function getBillId(): string
    {
        return $this->billId;
    }
}
