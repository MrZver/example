<?php

namespace Boodmo\Sales\Model\Workflow\Payment\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;

class AddCreditPointsCommand extends AbstractCommand
{
    /** @var float */
    private $total;

    /** @var string */
    private $currency;

    /** @var string */
    private $type;

    /** @var string|null */
    private $zohobooksId;

    /** @var int */
    private $bundleId;

    /**
     * @var int
     */
    private $customerId;

    /**
     * AddCreditPointsCommand constructor.
     *
     * @param int $customerId
     * @param float $total
     * @param string $currency
     * @param string $type
     * @param int $bundleId
     * @param string $zohobooksId
     */
    public function __construct(
        int $customerId,
        float $total,
        string $currency,
        string $type,
        int $bundleId,
        ?string $zohobooksId = null
    ) {
        parent::__construct();
        $this->total = $total;
        $this->currency = $currency;
        $this->type = $type;
        $this->zohobooksId = $zohobooksId;
        $this->customerId = $customerId;
        $this->bundleId = $bundleId;
    }

    /**
     * @return int
     */
    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    /**
     * @return float
     */
    public function getTotal() : float
    {
        return $this->total;
    }

    /**
     * @return mixed
     */
    public function getCurrency() : string
    {
        return $this->currency;
    }

    /**
     * @return mixed
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getZohobooksId(): string
    {
        return $this->zohobooksId ?? '';
    }

    /**
     * @return int
     */
    public function getBundleId(): int
    {
        return $this->bundleId;
    }
}
