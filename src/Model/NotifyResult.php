<?php

namespace Boodmo\Sales\Model;

use Boodmo\Sales\Model\Event\NotifyEvent;
use Zend\EventManager\EventManagerInterface;

final class NotifyResult
{
    /**
     * @var array
     */
    private $events = [];

    /**
     * NotifyResult constructor.
     *
     * @param array $events
     */
    public function __construct(array $events = [])
    {
        foreach ($events as $event) {
            $this->addEvent($event);
        }
    }

    public function addEvent(NotifyEvent $event): void
    {
        $this->events[] = $event;
    }

    public function getEvents(): array
    {
        return $this->events;
    }

    public function merge(self $another): void
    {
        $this->events = array_merge($this->events, $another->events);
    }

    public function triggerEvents(EventManagerInterface $eventManager): void
    {
        foreach ($this->getEvents() as $event) {
            $eventManager->triggerEvent($event);
        }
        $this->events = [];
    }
}
