<?php

namespace Boodmo\Sales\Model\Workflow;

use Boodmo\Sales\Model\Workflow\Status\StatusHistoryInterface;
use Boodmo\Sales\Model\Workflow\Status\StatusListInterface;
use Doctrine\ORM\Mapping as ORM;

trait EntityStatusHistoryTrait
{
    /**
     * @var array
     *
     * @ORM\Column(name="status_history", type="json_array", precision=0, scale=0, nullable=false, unique=false,
     *     options={"default" = "{}"}, columnDefinition="JSONB DEFAULT '{}'::jsonb NOT NULL")
     */
    protected $statusHistory = [];

    public function triggerStatusHistory(
        StatusListInterface $current,
        StatusListInterface $next,
        array $context = []
    ): void {
        $history = $this->getStatusHistory();
        array_unshift(
            $history,
            [
                StatusHistoryInterface::FROM => array_values($current->diff($next)->toArray()),
                StatusHistoryInterface::TO => array_values($next->diff($current)->toArray()),
                StatusHistoryInterface::TIMESTAMP => (new \DateTime())->getTimestamp(),
                StatusHistoryInterface::CONTEXT => $context
            ]
        );

        $this->setStatusHistory($history);
    }

    public function getStatusHistory(): array
    {
        return $this->statusHistory;
    }

    protected function setStatusHistory(array $statusHistory): void
    {
        $this->statusHistory = $statusHistory;
    }
}
