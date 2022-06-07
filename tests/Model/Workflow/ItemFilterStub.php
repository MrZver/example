<?php

namespace Boodmo\SalesTest\Model\Workflow;

use Boodmo\Sales\Model\Workflow\Status\InputItemList;

class ItemFilterStub
{
    public function __invoke(InputItemList $inputItemList): iterable
    {
        return $inputItemList;
    }
}
