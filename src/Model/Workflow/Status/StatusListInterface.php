<?php

namespace Boodmo\Sales\Model\Workflow\Status;

interface StatusListInterface extends \IteratorAggregate, \Countable
{
    public function toArray(): array;
    public function exists(StatusInterface $status): bool;
    public function diff(StatusListInterface $withList): StatusListInterface;
    public function aggregate(StatusListInterface $withList): StatusListInterface;
    public function add(StatusInterface $status): StatusListInterface;
    public function remove(StatusInterface $status): StatusListInterface;
}
