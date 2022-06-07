<?php

namespace Boodmo\Sales\Model\Workflow;

use Boodmo\Sales\Model\Workflow\Status\Status;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Model\Workflow\Status\StatusList;
use Boodmo\Sales\Model\Workflow\Status\StatusListInterface;
use Boodmo\Sales\Model\Workflow\Status\StatusProviderAggregateInterface;
use Boodmo\Sales\Model\Workflow\Status\StatusProviderInterface;
use Doctrine\ORM\Mapping as ORM;

trait EntityStatusTrait
{
    use EntityStatusHistoryTrait;
    /**
     * @var array
     *
     * @ORM\Column(name="status", type="json_array", precision=0, scale=0, nullable=false, unique=false,
     *     options={"default" = "{}"}, columnDefinition="JSONB DEFAULT '{}'::jsonb NOT NULL")
     */
    protected $status = [
        Status::TYPE_GENERAL => StatusEnum::NULL,
    ];


    /**
     * @return array
     */
    public function getStatus(): array
    {
        return $this->status;
    }

    /**
     * @param array $status
     *
     * @return $this
     */
    public function setStatus(array $status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return StatusListInterface|StatusList
     */
    public function getStatusList(): StatusListInterface
    {
        return new StatusList($this->getStatus());
    }

    public function setStatusList(StatusListInterface $statusList, array $context = []): StatusProviderInterface
    {
        if ($this->getStatusList()->diff($statusList)->count() === 0) {
            return $this;
        }
        $this->triggerStatusHistory($this->getStatusList(), $statusList, $context);
        $this->setStatus($statusList->toArray());
        $this->statusAggregate($this->getParent());

        return $this;
    }

    public function statusAggregate(?StatusProviderAggregateInterface $rootAggregate): void
    {
        if ($rootAggregate !== null) {
            /**
             * @var $aggregatorStatusList StatusList
             */
            if ($first = $rootAggregate->getChildren()->first()) {
                $aggregatorStatusList = $first->getStatusList();
                foreach ($rootAggregate->getChildren() as $child) {
                    $aggregatorStatusList = $aggregatorStatusList->aggregate($child->getStatusList());
                }
                $rootAggregate->setStatusList($aggregatorStatusList);
            }
        }
    }

    abstract public function getParent(): ?StatusProviderAggregateInterface;
}
