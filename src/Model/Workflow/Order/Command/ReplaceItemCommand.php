<?php


namespace Boodmo\Sales\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\Handler\ReplaceItemHandler;
use Boodmo\User\Entity\User;

class ReplaceItemCommand extends EditItemCommand
{
    protected const HANDLER = ReplaceItemHandler::class;

    /**
     * @var int
     */
    private $partId;

    private $updateDispatch = true;

    private $replacementMode = false;

    public function __construct(
        string $itemId,
        User $editor,
        int $price,
        int $cost,
        int $delivery,
        int $qty,
        ?int $partId,
        bool $updateDispatch = true,
        $replacementMode = false
    ) {
        // PartId instead of $supPartId
        parent::__construct($itemId, $editor, $price, $cost, $delivery, $qty);
        $this->partId         = $partId;
        $this->updateDispatch = $updateDispatch;
        $this->replacementMode = $replacementMode;
    }

    /**
     * @return null|int
     */
    public function getPartId(): ?int
    {
        return $this->partId;
    }

    /**
     * @return mixed
     */
    public function isUpdateDispatch()
    {
        return $this->updateDispatch;
    }

    /**
     * @return bool
     */
    public function isReplacementMode()
    {
        return $this->replacementMode;
    }
}
