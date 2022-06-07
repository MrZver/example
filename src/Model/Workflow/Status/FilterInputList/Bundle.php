<?php

namespace Boodmo\Sales\Model\Workflow\Status\FilterInputList;

use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Model\Workflow\Status\InputItemList;

class Bundle
{
    /**
     * @param InputItemList $inputItemList
     * @return iterable
     * @throws \RuntimeException
     */
    public function __invoke(InputItemList $inputItemList): iterable
    {
        /**
         * @var $item OrderItem
         */
        $item = $inputItemList->toArray()[0] ?? null;
        if ($item === null) {
            return;
        }
        $bundle = $item->getPackage()->getBundle();
        $items = [];
        foreach ($bundle->getPackages() as $package) {
            $items = array_merge($items, $package->getItems()->toArray());
        }
        if (count($items) !== count($inputItemList->toArray())) {
            throw new \RuntimeException(sprintf('wrong count items for filter (order id: %s)', $bundle->getId()));
        }
        $ids = array_map(function (OrderItem $item) {
            return (string)$item->getId();
        }, $items);

        foreach ($inputItemList as $item) {
            if (!in_array((string)$item->getId(), $ids)) {
                throw new \RuntimeException(
                    sprintf(
                        'Items does not belong to Bundle (item id: %s, order id: %s).',
                        $item->getId(),
                        $bundle->getId()
                    )
                );
            }
            yield $item;
        }
    }
}
