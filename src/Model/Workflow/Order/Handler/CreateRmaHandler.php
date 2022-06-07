<?php

namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Sales\Entity\OrderRma;
use Boodmo\Sales\Model\Workflow\Note\NotesMessage;
use Boodmo\Sales\Model\Workflow\Order\Command\CreateRmaCommand;
use Boodmo\Sales\Service\OrderService;

class CreateRmaHandler
{
    /** @var  OrderService */
    private $orderService;

    /**
     * CreateRmaHandler constructor.
     *
     * @param OrderService $orderService
     */
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * @param CreateRmaCommand $command
     * @throws \Exception
     */
    public function __invoke(CreateRmaCommand $command): void
    {
        $item = $this->orderService->loadOrderItem($command->getItemId());

        if ($command->getQty() > $item->getQty()
            || !isset(OrderRma::INTENTS[$command->getIntent()], OrderRma::REASONS[$command->getReason()])
        ) {
            throw new \RuntimeException(sprintf('Incorrect values for return (item id: %s)', $item->getId()), 422);
        }

        $return = (new OrderRma())
            ->setQty($command->getQty())
            ->setIntent(OrderRma::INTENTS[$command->getIntent()]['name'])
            ->setReason(OrderRma::REASONS[$command->getReason()]['name'])
            ->setOrderItem($item);
        $item->addRma($return);

        if (!empty($command->getNote())) {
            //Todo: do with add note command
            $note = new NotesMessage('OrderRma', $command->getNote(), $command->getUser());
            $return->addMessageToNotes($note);
        }

        $this->orderService->save($item->getPackage()->getBundle());
    }
}
