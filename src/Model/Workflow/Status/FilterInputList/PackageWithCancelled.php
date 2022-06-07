<?php

namespace Boodmo\Sales\Model\Workflow\Status\FilterInputList;

use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Model\Workflow\Status\InputItemList;

class PackageWithCancelled
{
    /**
     * @param InputItemList $inputItemList
     * @return iterable
     * @throws \RuntimeException
     */
    public function __invoke(InputItemList $inputItemList): iterable
    {
        /** @var $item OrderItem */
        $item = $inputItemList->toArray()[0] ?? null;
        if (is_null($item)) {
            return;
        }

        $package = $item->getPackage();
        $items = [];

        foreach ($package->getItems() as $item) {
            if ($item->isCancelled()) {
                $items[(string)$item->getId()] = $item;
            }
        }

        foreach ($inputItemList as $item) {
            if (!in_array((string)$item->getId(), array_keys($items))) {
                throw new \RuntimeException(
                    sprintf(
                        'Items does not belong to Package (item id: %s, package id: %s).',
                        $item->getId(),
                        $package->getId()
                    )
                );
            }
            yield $item;
        }
    }
}
