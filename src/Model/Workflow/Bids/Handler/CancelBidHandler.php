<?php

namespace Boodmo\Sales\Model\Workflow\Bids\Handler;

use Boodmo\Sales\Model\Workflow\Bids\Command\CancelBidCommand;
use Boodmo\Sales\Entity\OrderBid;
use Boodmo\Sales\Repository\OrderBidRepository;

class CancelBidHandler
{
    /** @var OrderBidRepository */
    private $orderBidRepository;

    /**
     * CancelBidHandler constructor.
     * @param OrderBidRepository $orderBidRepository
     */
    public function __construct(OrderBidRepository $orderBidRepository)
    {
        $this->orderBidRepository = $orderBidRepository;
    }

    /**
     * @param CancelBidCommand $command
     */
    public function __invoke(CancelBidCommand $command): void
    {
        $bid = $this->orderBidRepository->find($command->getBidId());
//        if ($bid->getSupplierProfile()->getId() == $command->getSupplier()->getId()) {
            $bid->setStatus(OrderBid::STATUS_CANCELLED);
            $this->orderBidRepository->save($bid);
//        }
    }
}
