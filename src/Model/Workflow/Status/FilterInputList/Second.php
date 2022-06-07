<?php

namespace Boodmo\Sales\Model\Workflow\Status\FilterInputList;

use Boodmo\Sales\Model\Workflow\Status\InputItemList;

class Second
{
    public function __invoke(InputItemList $inputItemList): iterable
    {
        $item = $inputItemList->toArray()[1] ?? null;
        if (is_null($item)) {
            return;
        }
        yield $item;
    }
}
