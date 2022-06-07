<?php

namespace Boodmo\Sales\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Checkout\ShoppingCart;
use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;
use Boodmo\User\Entity\User;

final class NewBundleCommand extends AbstractCommand
{
    /**
     * @var ShoppingCart
     */
    private $cart;
    /**
     * @var User
     */
    private $user;
    /**
     * @var array
     */
    private $additionalInfo;

    public function __construct(ShoppingCart $cart, User $user, array $additionalInfo)
    {
        parent::__construct();
        $this->cart = $cart;
        $this->user = $user;
        $this->additionalInfo = $additionalInfo;
    }

    /**
     * @return array
     */
    public function getAdditionalInfo(): array
    {
        return $this->additionalInfo;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return ShoppingCart
     */
    public function getCart(): ShoppingCart
    {
        return $this->cart;
    }
}
