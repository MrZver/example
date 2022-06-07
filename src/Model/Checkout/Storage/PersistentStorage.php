<?php

namespace Boodmo\Sales\Model\Checkout\Storage;

use Boodmo\Sales\Entity\Cart;
use Boodmo\Sales\Model\Checkout\CartStorageInterface;
use Boodmo\Sales\Repository\CartRepository;
use Zend\Stdlib\ArrayObject;

class PersistentStorage extends ArrayObject implements CartStorageInterface
{
    public const CART_ID_KEY = 'c_uid';
    /**
     * @var Cart
     */
    private $cart;
    /**
     * @var CartRepository
     */
    private $repository;
    /**
     * @var int
     */
    private $userId;
    /**
     * @var null|string
     */
    private $cUid;
    /**
     * @var ArrayObject
     */
    private $cidStorage;

    public function __construct(CartRepository $repository, ArrayObject $cidStorage, ?int $userId)
    {
        parent::__construct([], self::STD_PROP_LIST, 'ArrayIterator');
        $this->repository = $repository;
        $this->cUid = $cidStorage[self::CART_ID_KEY] ?? null;
        $this->userId = $userId;
        $this->cidStorage = $cidStorage;
        if (count($this->cidStorage[self::STORAGE_KEY_ITEMS] ?? []) > 0) {
            $this->exchangeArray($this->cidStorage->getArrayCopy());
        }
        //add CART_ID_KEY, if items are present in storage
        if ($this->cUid === null && count($this[self::STORAGE_KEY_ITEMS] ?? []) > 0) {
            $this->prepareCartIdKey();
        }
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($key)
    {
        switch ($key) {
            case self::STORAGE_KEY_ITEMS:
            case self::STORAGE_KEY_STEP:
            case self::STORAGE_KEY_EMAIL:
            case self::STORAGE_KEY_ADDRESS:
            case self::STORAGE_KEY_PAYMENT:
            case self::STORAGE_KEY_ORDER_ID:
                return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function &offsetGet($key)
    {
        $value = null;
        $cart = $this->getCart();
        switch ($key) {
            case self::STORAGE_KEY_ITEMS:
                $value = $cart->getItems();
                break;
            case self::STORAGE_KEY_STEP:
                $value = $cart->getStep();
                break;
            case self::STORAGE_KEY_EMAIL:
                $value = $cart->getEmail();
                break;
            case self::STORAGE_KEY_ADDRESS:
                $value = $cart->getAddress();
                break;
            case self::STORAGE_KEY_PAYMENT:
                $value = $cart->getPayment();
                break;
            case self::STORAGE_KEY_ORDER_ID:
                $value = $cart->getOrderId();
                break;
        }
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($key, $value)
    {
        $this->fill($key, $value);
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($key)
    {
        $this->fill($key, null);
    }

    public function exchangeArray($data)
    {
        $cart = $this->getCart();
        if (!$data) {
            $cart->clear(true);
            unset($this->cidStorage[self::STORAGE_KEY_ITEMS]);
        } else {
            $cart->setItems($data[self::STORAGE_KEY_ITEMS] ?? [])
                ->setStep($data[self::STORAGE_KEY_STEP] ?? null)
                ->setScope($data['scope'] ?? $cart::DEFAULT_SCOPE)
                //->setSessionId()
                ->setEmail($data[self::STORAGE_KEY_EMAIL] ?? null)
                ->setAddress($data[self::STORAGE_KEY_ADDRESS] ?? [])
                ->setPayment($data[self::STORAGE_KEY_PAYMENT] ?? [])
                ->setOrderId($data[self::STORAGE_KEY_ORDER_ID] ?? null);
        }
        $this->prepareCartIdKey();
        $this->repository->save($cart);
        return [];
    }

    private function fill(string $key, $value): void
    {
        $cart = $this->getCart();
        switch ($key) {
            case self::STORAGE_KEY_ITEMS:
                $cart->setItems($value ?? []);
                break;
            case self::STORAGE_KEY_STEP:
                $cart->setStep($value);
                break;
            case self::STORAGE_KEY_EMAIL:
                $cart->setEmail($value);
                break;
            case self::STORAGE_KEY_ADDRESS:
                $cart->setAddress($value);
                break;
            case self::STORAGE_KEY_PAYMENT:
                $cart->setPayment($value ?? []);
                break;
            case self::STORAGE_KEY_ORDER_ID:
                $cart->setOrderId($value);
                break;
        }
        $this->prepareCartIdKey();
        $this->repository->save($cart);
    }

    public function getCart(): Cart
    {
        if ($this->cart !== null) {
            return $this->cart;
        }
        if ($this->cUid === null && $this->userId === null) {
            $this->cart = new Cart();
        } elseif ($this->userId === null) {
            $this->cart = $this->repository->find($this->cUid) ?? new Cart();
        } else {
            $carts = $this->repository->findByIdOrUser($this->cUid, $this->userId);
            $found = count($carts);
            if ($found === 0) {
                $this->cart = new Cart();
            } elseif ($found === 1) {
                $this->cart = $carts[0];
            } else {
                $this->cart = $carts[0];
                foreach ($carts as $k => $cart) {
                    $this->cart->merge($cart);
                    if ($k > 0) {
                        $this->repository->rawDelete($cart);
                    }
                }
            }
        }
        if ($this->cart->getUser() === null && $this->userId !== null) {
            $this->cart->setUser($this->repository->findUser($this->userId));
        }
        $this->repository->save($this->cart);
        return $this->cart;
    }

    public function prepareCartIdKey(): self
    {
        $this->cidStorage[self::CART_ID_KEY] = $this->getCart()->getId();
        return $this;
    }
}
