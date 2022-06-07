<?php

namespace Boodmo\Sales\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;

final class SnoozeConfirmDateItemCommand extends AbstractCommand
{
    /** @var string */
    private $itemId;

    /** @var string */
    private $confirmationDate;

    public function __construct(string $itemId, $confirmationDate)
    {
//        if ($daysCount <= 0) {
//            throw new \RuntimeException(
//                sprintf('Days (%s) should be more than 0 (item id: %s).', $daysCount, $itemId)
//            );
//        }

        parent::__construct();
        $this->itemId = $itemId;
        $this->confirmationDate = $confirmationDate;
    }

    /**
     * @return string
     */
    public function getItemId(): string
    {
        return $this->itemId;
    }

    /**
     * @return string
     */
    public function getConfirmationDate(): string
    {
        return $this->confirmationDate;
    }
}
