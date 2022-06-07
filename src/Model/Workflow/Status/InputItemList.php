<?php

namespace Boodmo\Sales\Model\Workflow\Status;

use Traversable;

class InputItemList implements \IteratorAggregate
{
    private $list;

    /**
     * InputItemList constructor.
     *
     * @param array $items
     */
    public function __construct(array $items)
    {
        $this->list = (function (StatusProviderInterface ...$list) {
            return $list;
        })(...$items);
    }

    /**
     * @return array|StatusProviderInterface[]|StatusHistoryInterface[]
     */
    public function getSubjectList(): array
    {
        $list = [];
        foreach ($this->list as $item) {
            $package = $item->getParent();
            $bundle = $package->getParent();
            $list[spl_object_hash($item)] = $item;
            $list[spl_object_hash($package)] = $package;
            $list[spl_object_hash($bundle)] = $bundle;
        }
        return $list;
    }

    /**
     * Retrieve an external iterator
     *
     * @link  http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->list);
    }

    public function toArray(): array
    {
        return $this->list;
    }
}
