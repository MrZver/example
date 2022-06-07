<?php

namespace Boodmo\Sales\Model\Workflow\Note\Handler;

use Boodmo\Sales\Model\Workflow\Note\NotesableEntityIntarface;
use Boodmo\Sales\Model\Workflow\Note\Command\AddNotesCommand;
use Boodmo\Sales\Model\Workflow\Note\NotesMessage;
use Doctrine\ORM\EntityManager;

class AddNotesHandler
{
    /** @var EntityManager */
    private $entityManager;

    /**
     * AddNotesHandler constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function __invoke(AddNotesCommand $command): void
    {
        if ($repository = $this->entityManager->getRepository($command->getEntityClass())
            and $entity = $repository->find($command->getId())
            and $entity instanceof NotesableEntityIntarface
        ) {
            $message = new NotesMessage($command->getContext(), $command->getMessage(), $command->getAuthor());
            $entity->addMessageToNotes($message);
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
        }
    }
}
