<?php

namespace Boodmo\Sales\Model\Workflow\Payment\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;
use function foo\func;

class AddPaymentCommand extends AbstractCommand
{
    /**
     * @var int
     */
    private $customerId;

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
    private $transactionId;

    /**
     * @var string|null
     */
    private $zohobooksId = '';

    /** @var null|string */
    private $customId = null;
    /**
     * @var null|string
     */
    private $cashGateway;

    /**
     * AddPaymentCommand constructor.
     *
     * @param int         $customerId
     * @param float       $total
     * @param string      $currency
     * @param string      $method
     * @param string      $transactionId
     * @param string      $zohobooksId
     * @param null|string $customId
     * @param null|string $cashGateway
     *
     * @internal param int|null $packageId
     */
    public function __construct(
        int $customerId,
        float $total,
        string $currency,
        string $method,
        string $transactionId,
        ?string $zohobooksId = null,
        ?string $customId = null,
        ?string $cashGateway = null
    ) {
        parent::__construct();
        $this->customerId = $customerId;
        $this->total = $total;
        $this->currency = $currency;
        $this->method = $method;
        $this->transactionId = $transactionId;
        $this->zohobooksId = $zohobooksId;
        $this->customId = $customId;
        $this->cashGateway = $cashGateway;
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
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    /**
     * @return string
     */
    public function getZohobooksId(): string
    {
        return $this->zohobooksId ?? '';
    }

    /**
     * @return null|string
     */
    public function getCustomId()
    {
        return $this->customId;
    }

    public function getCashGateway(): ?string
    {
        return $this->cashGateway;
    }
}
