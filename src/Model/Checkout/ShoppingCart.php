<?php

namespace Boodmo\Sales\Model\Checkout;

use Boodmo\Sales\Model\Checkout\Storage\PersistentStorage;
use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Model\Offer;
use Boodmo\Sales\Model\OfferFilterInterface;
use Boodmo\Sales\Model\Seller;
use Boodmo\User\Model\AddressBook;
use Doctrine\Common\Collections\ArrayCollection;
use Money\Money;
use Zend\Stdlib\ArrayObject;

class ShoppingCart implements CartStorageInterface
{
    public const STEP_CART = 'shopping_cart';
    public const STEP_EMAIL = 'email';
    public const STEP_ADDRESS = 'address';
    public const STEP_REVIEW = 'review';
    public const STEP_PAYMENT = 'payment';

    private const CHECKOUT_STEPS = [
        self::STEP_CART,
        self::STEP_EMAIL,
        self::STEP_ADDRESS,
        self::STEP_REVIEW,
        self::STEP_PAYMENT,
    ];

    private const GA_STEPS = [
        self::STEP_CART => 'Cart',
        self::STEP_EMAIL => 'Email',
        self::STEP_ADDRESS => 'Delivery Address',
        self::STEP_REVIEW => 'Review Order',
        self::STEP_PAYMENT => 'Make Payment',
    ];
    /**
     * @var ArrayCollection|Offer[]
     */
    private $offers;
    /**
     * @var string
     */
    private $step = self::STEP_CART;
    /**
     * @var string
     */
    private $email;
    /**
     * @var AddressBook
     */
    private $address;

    private $paymentMethods = [];
    /**
     * @var ArrayObject
     */
    private $storage;

    /**
     * @var string
     */
    private $currency;

    public function __construct(array $offers, ArrayObject $storage)
    {
        $offers = (function (Offer ...$offers) {
            return $offers;
        })(...$offers);
        $this->offers = new ArrayCollection($offers);
        if (!isset($storage[self::STORAGE_KEY_ITEMS])) {
            $storage[self::STORAGE_KEY_ITEMS] = [];
        }
        $this->email = $storage[self::STORAGE_KEY_EMAIL] ?? '';
        $this->setStep($storage[self::STORAGE_KEY_STEP] ?: self::CHECKOUT_STEPS[0]);
        $this->setAddress(AddressBook::fromData($storage[self::STORAGE_KEY_ADDRESS] ?? []));
        $this->storage = $storage;
    }

    /**
     * @return Offer[]|ArrayCollection
     */
    public function getOffers(): ArrayCollection
    {
        return $this->offers;
    }

    public function getFilteredOffers(?OfferFilterInterface $offerFilter): ArrayCollection
    {
        $offers = $this->getOffers();
        if (is_null($offerFilter)) {
            return $offers;
        }
        return $offers->filter($offerFilter->toClosure());
    }

    public function existsOffer(Offer $offer): bool
    {
        $result = false;
        foreach ($this->offers as $offerIndex => $offerItem) {
            if ($offer->getProduct()->getId() === $offerItem->getProduct()->getId()) {
                $result = true;
                break;
            }
        }
        return $result;
    }

    public function getOfferByOffer(Offer $offer): ?Offer
    {
        $result = null;
        foreach ($this->offers as $offerIndex => $offerItem) {
            if ($offer->getProduct()->getId() === $offerItem->getProduct()->getId()) {
                $result = $offerItem;
                break;
            }
        }
        return $result;
    }

    public function isEmpty(): bool
    {
        return $this->getOffers()->isEmpty();
    }

    public function addOffer(Offer $offer): void
    {
        if ($this->offers->contains($offer)) {
            return;
        }
        $product = $offer->getProduct();
        $this->offers->set($product->getId(), $offer);
        $items = $this->storage[self::STORAGE_KEY_ITEMS];
        $items[$product->getId()] = $product->getRequestedQty();
        $this->storage[self::STORAGE_KEY_ITEMS] = $items;
        $this->setStep(self::STEP_CART);
    }

    public function removeOffer(Offer $offer): void
    {
        foreach ($this->offers as $offerIndex => $offerItem) {
            if ($offer->getProduct()->getId() === $offerItem->getProduct()->getId()) {
                $this->offers->remove($offerIndex);
                $items = $this->storage[self::STORAGE_KEY_ITEMS];
                unset($items[$offer->getProduct()->getId()]);
                $this->storage[self::STORAGE_KEY_ITEMS] = $items;
                break;
            }
        }
    }

    public function clearAll(int $orderId = null): void
    {
        $this->getOffers()->clear();
        foreach ($this->storage as $key => $item) {
            unset($this->storage[$key]);
        }
        if ($this->storage instanceof PersistentStorage) {
            $this->storage->exchangeArray([]);
        }
        $this->storage[self::STORAGE_KEY_ORDER_ID] = $orderId;
        if (is_null($orderId)) {
            unset($this->storage[self::STORAGE_KEY_ORDER_ID]);
        }
    }

    public function getLastOrderId(): ?int
    {
        return $this->storage[self::STORAGE_KEY_ORDER_ID] ?? null;
    }

    /**
     * @return array|Seller[]
     */
    public function getListSeller(): array
    {
        return array_unique($this->getOffers()->map(function (Offer $offer): Seller {
            return $offer->getProduct()->getSeller();
        })->toArray());
    }

    /**
     * This method for count ALL total QUANTITY of Offers in Shopping Cart
     * @return int
     */
    public function getTotalCountItems(): int
    {
        return array_reduce($this->getOffers()->toArray(), function (int $sum, Offer $offer) {
            return $sum + $offer->getProduct()->getRequestedQty();
        }, 0);
    }

    /**
     * This method for count all total OFFERS in Shopping Cart
     * @return int
     */
    public function getTotalItems(): int
    {
        return $this->getOffers()->count();
    }

    public function getBaseSubTotal(OfferFilterInterface $filter = null): Money
    {
        return array_reduce($this->getFilteredOffers($filter)->toArray(), function (?Money $sum, Offer $offer) {
            $total = $offer->getProduct()->getBasePrice()->multiply($offer->getProduct()->getRequestedQty());
            return ($sum) ? $sum->add($total) : $total;
        }, null);
    }

    public function getBaseDeliveryTotal(OfferFilterInterface $filter = null): Money
    {
        return array_reduce($this->getFilteredOffers($filter)->toArray(), function (?Money $sum, Offer $offer) {
            $total = $offer->getDelivery()->getBasePrice()->multiply($offer->getProduct()->getRequestedQty());
            return ($sum) ? $sum->add($total) : $total;
        }, null);
    }

    public function getBaseGrandTotal(OfferFilterInterface $filter = null): Money
    {
        return $this->getBaseSubTotal($filter)->add($this->getBaseDeliveryTotal($filter));
    }

    public function nextStep(string $currentStep): string
    {
        $key = array_search($currentStep, self::CHECKOUT_STEPS, true);
        if ($key === false) {
            return '';
        }
        return self::CHECKOUT_STEPS[$key+1] ?? '';
    }

    public function getStep(): string
    {
        return $this->step;
    }

    public function setStep(string $step): void
    {
        if (!in_array($step, self::CHECKOUT_STEPS)) {
            return;
        }
        $this->step = $step;
        $this->storage[self::STORAGE_KEY_STEP] = $step;
    }

    /**
     * Return index of step by name. Return -1 if step name not found
     * @param string $stepName
     * @return int
     */
    public function getStepIndexByName(string $stepName = null) : int
    {
        $stepName = $stepName ?? $this->getStep();
        $steps = array_flip(self::CHECKOUT_STEPS);
        return $steps[$stepName] ?? -1;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->storage[self::STORAGE_KEY_EMAIL] = $email;
        $this->email = $email;
    }

    /**
     * @return AddressBook
     */
    public function getAddress(): AddressBook
    {
        return $this->address;
    }

    /**
     * @param AddressBook $address
     *
     * @return void
     */
    public function setAddress(AddressBook $address): void
    {
        $this->address = $address;
        $this->storage[self::STORAGE_KEY_ADDRESS] = $address->toArray();
    }

    public function setPaymentMethods(array $paymentMethods): void
    {
        $this->paymentMethods = $paymentMethods;
        $this->storage[self::STORAGE_KEY_PAYMENT] = $paymentMethods;
    }

    public function getPaymentMethods(): array
    {
        return $this->paymentMethods;
    }

    public function isReadyForOrder(): bool
    {
        return !$this->isEmpty() && $this->getEmail() !== '' && !$this->getAddress()->isEmpty();
            //&& count($this->getPaymentMethods()) > 0;
    }

    public function toArray(): array
    {
        foreach ($this->offers as $offer) {
            $offer = $offer->toArray();
            $cart[$offer['product']['id']] = $offer;
        }
        return $cart ?? [];
    }

    public function applyCurrency(string $currency): self
    {
        $this->setCurrency($currency);
        $offers = $this->offers->map(function (Offer $offer) use ($currency) {
            return $offer->toCurrency($currency);
        })->toArray();
        return new self($offers, $this->storage);
    }

    public function getCurrentStepName()
    {
        return self::GA_STEPS[$this->getStep()];
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return empty($this->currency) ? MoneyService::BASE_CURRENCY : $this->currency;
    }

    /**
     * @param string $currency
     *
     * @return $this
     */
    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }
}
