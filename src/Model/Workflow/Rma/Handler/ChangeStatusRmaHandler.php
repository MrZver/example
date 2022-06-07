<?php

namespace Boodmo\Sales\Model\Workflow\Rma\Handler;

use Boodmo\Sales\Entity\OrderRma;
use Boodmo\Sales\Model\Workflow\Rma\Command\ChangeStatusRmaCommand;
use Boodmo\Sales\Repository\OrderRmaRepository;
use Boodmo\Seo\Service\BetaoutService;

class ChangeStatusRmaHandler
{
    /** @var OrderRmaRepository */
    private $orderRmaRepository;

    /**
     * @var BetaoutService
     */
    private $betaoutService;

    /**
     * ChangeStatusRmaHandler constructor.
     * @param OrderRmaRepository $orderRmaRepository
     * @param BetaoutService $betaoutService
     */
    public function __construct(OrderRmaRepository $orderRmaRepository, BetaoutService $betaoutService)
    {
        $this->orderRmaRepository = $orderRmaRepository;
        $this->betaoutService = $betaoutService;
    }

    /**
     * @param ChangeStatusRmaCommand $command
     * @throws \RuntimeException
     */
    public function __invoke(ChangeStatusRmaCommand $command): void
    {
        /* @var OrderRma $entity */
        if ($entity = $this->orderRmaRepository->find($command->getId())) {
            if ($command->getStatus() === $entity->getStatus()) {
                throw new \RuntimeException(
                    sprintf('Rma (id: %s) is already in this status (%s)', $entity->getId(), $entity->getStatus()),
                    422
                );
            }
            if (!in_array($command->getStatus(), OrderRma::STATUSES, true)) {
                throw new \RuntimeException(
                    sprintf('Wrong new status (%s) (rma id: %s)', $command->getStatus(), $entity->getId()),
                    422
                );
            }
            if (array_key_exists($entity->getStatus(), OrderRma::STATUSES_TRANSITIONS)
                && in_array($command->getStatus(), OrderRma::STATUSES_TRANSITIONS[$entity->getStatus()])) {
                $entity->setStatus($command->getStatus());
                $this->orderRmaRepository->save($entity);
                if ($command->getStatus() === OrderRma::STATUS_COMPLETED) {
                    $this->betaoutService->processRmaReturn($entity);
                }
            } else {
                throw new \RuntimeException(
                    sprintf(
                        'Can not change Rma to this new status (%s) (rma id: %s)',
                        $entity->getStatus(),
                        $entity->getId()
                    ),
                    422
                );
            }
        }
    }
}
