<?php

namespace Boodmo\Sales\Model\Workflow\Payment\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;

class EditCreditPointsCommand extends AbstractCommand
{
    /** @var float */
    private $total;

    /** @var string */
    private $currency;

    /** @var string */
    private $type;

    /** @var string */
    private $zohobooksId;

    /**
     * @var string
     */
    private $creditPointId;

    /**
     * AddCreditPointsCommand constructor.
     *
     * @param string $creditPointId
     * @param float $total
     * @param string $currency
     * @param string $type
     * @param string $zohobooksId
     */
    public function __construct(
        string $creditPointId,
        float $total,
        string $currency,
        string $type,
        string $zohobooksId = ''
    ) {
        parent::__construct();
        $this->creditPointId = $creditPointId;
        $this->total = $total;
        $this->currency = $currency;
        $this->type = $type;
        $this->zohobooksId = $zohobooksId;
    }

    /**
     * @return string
     */
    public function getCreditPointId(): string
    {
        return $this->creditPointId;
    }

    /**
     * @return mixed
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
        return $this->zohobooksId;
    }
}
