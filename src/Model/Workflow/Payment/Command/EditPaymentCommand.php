<?php

namespace Boodmo\Sales\Model\Workflow\Payment\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;

class EditPaymentCommand extends AbstractCommand
{
    /**
     * @var string
     */
    private $paymentId;

    /**
     * @var string
     */
    private $zohobooksId = '';

    /**
     * @var string
     */
    private $transactionId;

    /**
     * @var float
     */
    private $total;

    /**
     * @var string
     */
    private $method;

    /**
     * AddPaymentCommand constructor.
     *
     * @param string    $paymentId
     * @param string    $zohobooksId
     * @param string    $transactionId
     * @param float     $total
     * @param string    $method
     */
    public function __construct(
        string $paymentId,
        string $zohobooksId,
        string $transactionId,
        float $total,
        string $method
    ) {
        parent::__construct();
        $this->paymentId = $paymentId;
        $this->zohobooksId = $zohobooksId;
        $this->transactionId = $transactionId;
        $this->total = $total;
        $this->method = $method;
    }

    /**
     * @return string
     */
    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    /**
     * @return string
     */
    public function getZohobooksId(): string
    {
        return $this->zohobooksId;
    }

    /**
     * @return string
     */
    public function getTransactionId(): string
    {
        return $this->transactionId;
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
    public function getMethod(): string
    {
        return $this->method;
    }
}
