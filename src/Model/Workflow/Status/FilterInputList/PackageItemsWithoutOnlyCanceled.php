<?php

namespace Boodmo\Sales\Model\Workflow\Status\FilterInputList;

use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Model\Workflow\Status\InputItemList;
use Boodmo\Sales\Model\Workflow\Status\Status;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;

class PackageItemsWithoutOnlyCanceled
{
    public function __invoke(InputItemList $inputItemList): iterable
    {
        /** @var OrderItem $item */
        $item = $inputItemList->toArray()[0] ?? null;
        if ($item === null) {
            return;
        }

        foreach ($item->getPackage()->getItems() as $item) {
            $status = $item->getStatus();
            $isOnlyCanceled = count($status) == 1 && isset($status[Status::TYPE_GENERAL])
                && $status[Status::TYPE_GENERAL] == StatusEnum::CANCELLED;
            if ($item->isCancelled() and !$isOnlyCanceled) {
                yield $item;
            }
        }
    }
}
