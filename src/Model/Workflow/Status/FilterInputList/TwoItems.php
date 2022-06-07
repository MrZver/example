<?php

namespace Boodmo\Sales\Model\Workflow\Status\FilterInputList;

use Boodmo\Sales\Model\Workflow\Status\InputItemList;

class TwoItems
{
    /**
     * @param InputItemList $inputItemList
     * @return iterable
     * @throws \InvalidArgumentException
     */
    public function __invoke(InputItemList $inputItemList): iterable
    {
        $items = $inputItemList->toArray();
        if (count($items) !== 2) {
            throw new \InvalidArgumentException('Filter required 2 items in list.');
        }
        yield $items[0];
        yield $items[1];
    }
}
