<?php

namespace Boodmo\Sales\Model\Workflow\Order\Command;

use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Shipping\Entity\ShippingBox;
use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;
use Boodmo\Sales\Model\Workflow\Order\Handler\SetFlagsHandler;

class SetFlagsCommand extends AbstractCommand
{
    protected const HANDLER = SetFlagsHandler::class;

    private const ENTITY_MAP = [
        'OrderPackage' => OrderPackage::class,
        'OrderItem' => OrderItem::class,
        'ShippingBox' => ShippingBox::class,
    ];

    /** @var string */
    private $id;

    /** @var string */
    private $entity;

    /** @var int */
    private $flag;

    /* @var bool*/
    private $mode;

    /**
     * AddNotesCommand constructor.
     *
     * @param string $id
     * @param string $entity
     * @param int $flag
     * @param bool $mode
     */
    public function __construct(string $id, string $entity, int $flag, bool $mode)
    {
        parent::__construct();
        $this->id = $id;
        $this->entity = $entity;
        $this->flag = $flag;
        $this->mode = $mode;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEntity(): string
    {
        return $this->entity;
    }

    /**
     * @return int
     */
    public function getFlag(): int
    {
        return $this->flag;
    }

    /**
     * @return bool
     */
    public function getMode(): bool
    {
        return $this->mode;
    }

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        $result = '';
        if ($entity = $this->getEntity() and isset(self::ENTITY_MAP[$entity])) {
            $result = self::ENTITY_MAP[$entity];
        }
        return $result;
    }
}
