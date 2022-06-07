<?php

namespace Boodmo\Sales\Model\Workflow\Payment\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;

class PayToBillCommand extends AbstractCommand
{
    /** @var string */
    private $billId;

    /** @var array */
    private $paymentInfo;

    /** @var array */
    private $creditPointInfo;

    /** @var string */
    private $appliedId;

    public function __construct(
        string $billId,
        array $paymentInfo = [],
        array $creditPointInfo = [],
        string $appliedId = null
    ) {
        parent::__construct();
        $this->billId = $billId;
        $this->paymentInfo = $paymentInfo;
        $this->creditPointInfo = $creditPointInfo;
        $this->appliedId = $appliedId;
    }

    /**
     * @return string
     */
    public function getBillId(): string
    {
        return $this->billId;
    }

    /**
     * @return array
     */
    public function getPaymentInfo(): array
    {
        return $this->paymentInfo;
    }

    /**
     * @return array
     */
    public function getCreditPointInfo(): array
    {
        return $this->creditPointInfo;
    }

    /**
     * @return string
     */
    public function getAppliedId(): ?string
    {
        return $this->appliedId;
    }
}
