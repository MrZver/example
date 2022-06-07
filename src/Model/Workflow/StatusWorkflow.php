<?php

namespace Boodmo\Sales\Model\Workflow;

use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Model\Event\NotifyEvent;
use Boodmo\Sales\Model\NotifyResult;
use Boodmo\Sales\Model\Workflow\Status\FilterInputList\Bundle;
use Boodmo\Sales\Model\Workflow\Status\FilterInputList\First;
use Boodmo\Sales\Model\Workflow\Status\FilterInputList\PackageItemsWithoutOnlyCanceled;
use Boodmo\Sales\Model\Workflow\Status\FilterInputList\PackageWithCancelled;
use Boodmo\Sales\Model\Workflow\Status\FilterInputList\PackageWithoutCancelled;
use Boodmo\Sales\Model\Workflow\Status\FilterInputList\Second;
use Boodmo\Sales\Model\Workflow\Status\FilterInputList\TwoItems;
use Boodmo\Sales\Model\Workflow\Status\InputItemList;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Model\Workflow\Status\StatusList;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Model\Workflow\Status\StatusHistoryInterface;
use Boodmo\Sales\Model\Workflow\Status\StatusProviderInterface;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\EventManager\ListenerAggregateInterface;

class StatusWorkflow implements EventManagerAwareInterface
{
    use EventManagerAwareTrait;

    public const FILTER_INPUT_LIST_FIRST = First::class;
    public const FILTER_INPUT_LIST_PACKAGE_WITHOUT_CANCEL = PackageWithoutCancelled::class;
    public const FILTER_INPUT_LIST_ALL_BUNDLE = Bundle::class;
    public const FILTER_INPUT_LIST_SECOND = Second::class;
    public const FILTER_INPUT_LIST_TWO_ITEMS = TwoItems::class;
    public const FILTER_INPUT_LIST_CANCELLED = PackageWithCancelled::class;
    public const FILTER_INPUT_LIST_CANCELLED_WITHOUT_ONLY_CANCELED = PackageItemsWithoutOnlyCanceled::class;

    protected $subjectHistoryList = [];
    protected $notifyResult;
    /**
     * @var ListenerAggregateInterface
     */
    private $listenerAggregate;

    public function __construct(ListenerAggregateInterface $listenerAggregate = null)
    {
        $this->notifyResult = new NotifyResult();
        $this->listenerAggregate = $listenerAggregate;
    }

    /**
     * @param TransitionEventInterface $event
     * @return NotifyResult
     * @throws \RuntimeException
     */
    public function raiseTransition(TransitionEventInterface $event): NotifyResult
    {
        $results = $this->getEventManager()->triggerEventUntil(function ($r) {
            return ($r instanceof NotifyResult);
        }, $event);
        if (!($results->last() instanceof NotifyResult)) {
            throw new \RuntimeException(sprintf('No result after transition (%s).', $event->getName()));
        }
        return $results->last();
    }

    public function buildInputItemList(array $items): InputItemList
    {
        return new InputItemList($items);
    }

    protected function attachDefaultListeners()
    {
        if ($this->listenerAggregate !== null) {
            $this->listenerAggregate->attach($this->events);
        }
        $this->events->attach('*', \Closure::fromCallable([$this, 'collectHistory']), 100);
        $this->events->attach('*', \Closure::fromCallable([$this, 'transition']), 0);
        $this->events->attach('*', \Closure::fromCallable([$this, 'collectNotify']), -100);
    }

    protected function collectNotify(TransitionEventInterface $event): NotifyResult
    {
        $subjectList = $event->getTarget()->getSubjectList();
        foreach ($this->subjectHistoryList as $hash => $history) {
            $lastHistory = $subjectList[$hash]->getStatusHistory();
            if (count($lastHistory) > count($history)) {
                foreach ($lastHistory[0][StatusHistoryInterface::TO] as $code) {
                    $this->notifyResult->addEvent(new NotifyEvent(
                        '*->' . $code . '[' . get_class($subjectList[$hash]) . ']',
                        $subjectList[$hash],
                        $lastHistory[0]
                    ));
                }
            }
        }
        return $this->notifyResult;
    }

    protected function collectHistory(TransitionEventInterface $event): void
    {
        $this->subjectHistoryList = [];
        foreach ($event->getTarget()->getSubjectList() as $hash => $subject) {
            $this->subjectHistoryList[$hash] = $subject->getStatusHistory();
        }
    }

    /**
     * @param TransitionEventInterface $event
     * @throws \RuntimeException
     */
    protected function transition(TransitionEventInterface $event): void
    {
        /* @var OrderItem $item*/
        if (!$event->isActive()) {
            $item = $event->getTarget()->toArray()[0];
            $status = $item->getStatus();
            throw new \RuntimeException(
                sprintf(
                    'Transition [%s] is not active for target item(s). Entity id: %s, Entity status: %s',
                    $event->getName(),
                    $item->getId(),
                    \json_encode($item->getStatus())
                )
            );
        }
        /**
         * @var $item StatusProviderInterface|StatusHistoryInterface
         */
        $statusItemList = [];
        foreach ($event->getInputRule() as $code => $handlerClass) {
            $filterItemHandler = new $handlerClass();
            foreach ($filterItemHandler($event->getTarget()) as $item) {
                $newStatusList = $item->getStatusList();
                $id = (string)$item->getId();
                if (isset($statusItemList[$id])) {
                    $newStatusList = $statusItemList[$id];
                }
                $statusItemList[$id] = $newStatusList->remove(StatusEnum::build($code));
            }
        }
        if (count($event->getInputRule()) === 0) {
            foreach ($event->getTarget() as $item) {
                $statusItemList[(string)$item->getId()] = new StatusList(null);
            }
        }
        foreach ($event->getOutputRule() as $code => $handlerClass) {
            $filterItemHandler = new $handlerClass();
            foreach ($filterItemHandler($event->getTarget()) as $item) {
                $id = (string)$item->getId();
                if (isset($statusItemList[$id])) {
                    $statusItemList[$id] = $statusItemList[$id]->add(StatusEnum::build($code));
                }
            }
        }

        foreach ($event->getTarget() as $item) {
            $id = (string)$item->getId();
            if (isset($statusItemList[$id])) {
                $item->setStatusList($statusItemList[$id], $event->getContext());
            }
        }
    }
}
