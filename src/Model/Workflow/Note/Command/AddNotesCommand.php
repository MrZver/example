<?php

namespace Boodmo\Sales\Model\Workflow\Note\Command;

use Boodmo\Sales\Entity\CreditMemo;
use Boodmo\Sales\Entity\OrderBid;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;
use Boodmo\Sales\Model\Workflow\Note\Handler\AddNotesHandler;
use Boodmo\Shipping\Entity\ShippingBox;
use Boodmo\User\Entity\User;
use Boodmo\Sales\Entity\OrderRma;

class AddNotesCommand extends AbstractCommand
{
    protected const HANDLER = AddNotesHandler::class;

    private const ENTITY_MAP = [
        'OrderBundle' => OrderBundle::class,
        'OrderPackage' => OrderPackage::class,
        'OrderItem' => OrderItem::class,
        'CreditMemo' => CreditMemo::class,
        'ShippingBox' => ShippingBox::class,
        'OrderRma' => OrderRma::class,
        'OrderBid' => OrderBid::class,
    ];

    /** @var string */
    private $id;

    /** @var string */
    private $context;

    /** @var string */
    private $message;

    /**
     * @var User
     */
    private $author;

    /** @var string */
    private $entity;

    /**
     * AddNotesCommand constructor.
     *
     * @param string $id
     * @param string $context
     * @param string $message
     * @param string $entity
     */
    public function __construct(string $id, string $context, string $message, User $author, string $entity = '')
    {
        parent::__construct();
        $this->id = $id;
        $this->context = $context;
        $this->message = $message;
        $this->author = $author;
        $this->entity = $entity;
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
    public function getContext(): string
    {
        return $this->context;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return User
     */
    public function getAuthor(): User
    {
        return $this->author;
    }

    /**
     * @return string
     */
    public function getEntity(): string
    {
        return $this->entity;
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
