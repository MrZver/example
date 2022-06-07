<?php

namespace Boodmo\Sales\Model\Workflow\Status;

use ArrayIterator;
use Traversable;

final class StatusList implements StatusListInterface
{
    private $list = [];

    /**
     * StatusList constructor.
     *
     * @param array $statuses
     */
    public function __construct(array $statuses = null)
    {
        if (is_array($statuses) && empty($statuses)) {
            $statuses[] = '';
        }
        foreach (((array) $statuses) as $code) {
            $status = StatusEnum::build($code);
            $this->list[$status->getType()->getCode()] = $status;
        }
    }
    /**
     * Retrieve an external iterator
     *
     * @link  http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->list);
    }

    public function exists(StatusInterface $status): bool
    {
        foreach ($this->list as $st) {
            if ($st->getCode() === $status->getCode()) {
                return true;
            }
        }
        return false;
    }

    public function add(StatusInterface $status): StatusListInterface
    {
        if (!$this->exists($status)) {
            $list = $this->list;
            $list[$status->getType()->getCode()] = $status;
            return new self(array_map(function (StatusInterface $status) {
                return $status->getCode();
            }, $list));
        }
        return clone $this;
    }

    public function get(string $type): ?StatusInterface
    {
        return $this->list[$type] ?? null;
    }

    /**
     * @param string $type
     * @return StatusInterface
     * @throws \RuntimeException
     */
    public function fallbackStatus(string $type): StatusInterface
    {
        if (!isset($this->list[Status::TYPE_GENERAL])) {
            throw new \RuntimeException('Status list is empty.');
        }
        return $this->get($type) ?? $this->list[Status::TYPE_GENERAL];
    }

    public function remove(StatusInterface $status): StatusListInterface
    {
        if ($this->exists($status)) {
            $list = $this->list;
            unset($list[$status->getType()->getCode()]);
            return new self(array_map(function (StatusInterface $status) {
                return $status->getCode();
            }, $list));
        }
        return clone $this;
    }

    public function aggregate(StatusListInterface $withList): StatusListInterface
    {
        $newList = $this->toArray() + $withList->toArray();
        $statusList = iterator_to_array($withList);
        foreach (array_intersect(array_keys($this->list), array_keys($statusList)) as $type) {
            if (($statusList[$type]->getWeight() <=> $this->list[$type]->getWeight()) < 0) {
                $newList[$type] = $statusList[$type]->getCode();
            }
        }
        return new self($newList);
    }

    public function diff(StatusListInterface $withList): StatusListInterface
    {
        $diff = array_diff(array_values($this->toArray()), array_values($withList->toArray()));
        return new self(!empty($diff) ? $diff : null);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return array_map(function (StatusInterface $status) {
            return $status->getCode();
        }, $this->list);
    }

    /**
     * Count elements of an object
     *
     * @link  http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count(): int
    {
        return count($this->list);
    }
}
