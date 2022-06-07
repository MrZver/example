<?php

namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Sales\Model\Workflow\Order\Command\SetFlagsCommand;
use Doctrine\ORM\EntityManager;

class SetFlagsHandler
{
    /** @var EntityManager */
    private $entityManager;

    /**
     * SetFlagsHandler constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function __invoke(SetFlagsCommand $command): void
    {
        if ($repository = $this->entityManager->getRepository($command->getEntityClass())
            and $entity = $repository->find($command->getId())
        ) {
            $newFlag = $command->getMode()
                ? $entity->getFlags() | $command->getFlag()
                : $entity->getFlags() & ~$command->getFlag();
            $entity->setFlags($newFlag);
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
        }
    }
}
