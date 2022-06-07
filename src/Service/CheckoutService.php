<?php

namespace Boodmo\Sales\Service;

use Boodmo\Catalog\Service\PartService;
use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Model\Checkout\CheckoutResult;
use Boodmo\Sales\Model\Checkout\InputFilterList;
use Boodmo\Sales\Model\Checkout\ShoppingCart;
use Boodmo\Sales\Model\Checkout\Storage\PersistentStorage;
use Boodmo\Sales\Model\Payment\PaymentProviderInterface;
use Boodmo\Sales\Model\Workflow\Order\Command\NewBundleCommand;
use Boodmo\Sales\Repository\CartRepository;
use Boodmo\Shipping\Model\Location;
use Boodmo\Shipping\Service\ShippingService;
use Boodmo\User\Entity\User;
use Boodmo\User\Model\AddressBook;
use Boodmo\User\Service\SupplierService;
use Boodmo\User\Service\UserService;
use Prooph\ServiceBus\CommandBus;
use Zend\Session\Container;
use Zend\Stdlib\ArrayObject;

class CheckoutService
{
    public const TYPE_STORAGE_MEMORY = 'memory';
    public const TYPE_STORAGE_SESSION = 'session';
    public const STORAGE_SESSION_NAME = 'Session\Checkout';
    public const RAZORPAY_DELIVERY_DISCOUNT_KOEF = 0.5;

    /**
     * @var UserService
     */
    private $userService;
    /**
     * @var SalesService
     */
    private $salesService;
    /**
     * @var SupplierService
     */
    private $supplierService;
    /**
     * @var PartService
     */
    private $partService;
    /**
     * @var OrderService
     */
    private $orderService;
    /**
     * @var PaymentService
     */
    private $paymentService;
    /**
     * @var InputFilterList
     */
    private $inputFilterList;
    /**
     * @var CommandBus
     */
    private $commandBus;
    /**
     * @var CartRepository
     */
    private $cartRepository;

    /**
     * @var ShippingService
     */
    private $shippingService;

    /**
     * CheckoutService constructor.
     *
     * @param UserService     $userService
     * @param SalesService    $salesService
     * @param SupplierService $supplierService
     * @param PartService     $partService
     * @param CommandBus      $commandBus
     * @param OrderService    $orderService
     * @param PaymentService  $paymentService
     * @param InputFilterList $inputFilterList
     * @param CartRepository  $cartRepository
     * @param ShippingService $shippingService
     */
    public function __construct(
        UserService $userService,
        SalesService $salesService,
        SupplierService $supplierService,
        PartService $partService,
        CommandBus $commandBus,
        OrderService $orderService,
        PaymentService $paymentService,
        InputFilterList $inputFilterList,
        CartRepository $cartRepository,
        ShippingService $shippingService
    ) {
        $this->userService = $userService;
        $this->salesService = $salesService;
        $this->supplierService = $supplierService;
        $this->partService = $partService;
        $this->commandBus = $commandBus;
        $this->orderService = $orderService;
        $this->paymentService = $paymentService;
        $this->inputFilterList = $inputFilterList;
        $this->cartRepository = $cartRepository;
        $this->shippingService = $shippingService;
    }

    public function getCart(?string $currency = null): ShoppingCart
    {
        $storage = $this->getStorage(CheckoutService::TYPE_STORAGE_SESSION);
        $cart = $this->buildCart($storage);
        return $currency ? $cart->applyCurrency($currency) : $cart;
    }

    public function addItemCartById(ShoppingCart $cart, int $productId, int $qty): bool
    {
        if ($offer = $this->salesService->getOfferByProductId($productId, new Location(), $cart->getCurrency())) {
            if ($offerInCart = $cart->getOfferByOffer($offer)) {
                $cart->addOffer($offerInCart->inquiryQty($qty));
            } else {
                $cart->addOffer($offer->inquiryQty($qty));
            }
        }
        return true;
    }

    public function editItemCartById(ShoppingCart $cart, int $productId, int $qty): bool
    {
        if ($offer = $this->salesService->getOfferByProductId($productId, new Location(), $cart->getCurrency())) {
            if ($cart->existsOffer($offer)) {
                $cart->removeOffer($offer);
            }
            $cart->addOffer($offer->inquiryQty($qty));
        }
        return true;
    }

    public function removeItemCartById(ShoppingCart $cart, int $productId): bool
    {
        if ($offer = $this->salesService->getOfferByProductId($productId, new Location(), $cart->getCurrency())) {
            $cart->removeOffer($offer);
        }
        return true;
    }

    /**
     * @param string $type
     * @return ArrayObject
     * @throws \InvalidArgumentException
     */
    public function getStorage(string $type): ArrayObject
    {
        $checkoutStorage = null;
        switch ($type) {
            case self::TYPE_STORAGE_MEMORY:
                $checkoutStorage = new ArrayObject();
                break;
            case self::TYPE_STORAGE_SESSION:
                $checkoutStorage = new Container(self::STORAGE_SESSION_NAME);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unknown type storage: %s', $type));
        }
        return $checkoutStorage;
    }

    public function getPersistStorage(ArrayObject $idStorage): PersistentStorage
    {
        return new PersistentStorage(
            $this->cartRepository,
            $idStorage,
            $this->userService->getAuthIdentityUser()->getId()
        );
    }

    public function buildCart(ArrayObject $storage): ShoppingCart
    {
        $storage = $this->getPersistStorage($storage);
        $offers = [];
        $location = AddressBook::fromData($storage[ShoppingCart::STORAGE_KEY_ADDRESS] ?? [])->toLocation();
        foreach ($storage[ShoppingCart::STORAGE_KEY_ITEMS] ?? [] as $id => $qty) {
            if ($offer = $this->salesService->getOfferByProductId($id, $location)) {
                $offers[$id] = $offer->inquiryQty($qty);
            }
        }
        return new ShoppingCart($offers, $storage);
    }

    public function initOnepage(ShoppingCart $cart): CheckoutResult
    {
        // During init onepage checkout change current step (if previously will cart page)
        if ($cart->getStep() === $cart::STEP_CART) {
            $cart->setStep($cart->nextStep($cart->getStep()));
        }
        $loggedUser = $this->userService->getAuthIdentityUser();
        //If user logged in then fill email automatically
        if ($loggedUser->getId()) {
            $cart->setEmail($loggedUser->getEmail());
        }
        // If user logged in then omit current EMAIL step to next
        if ($loggedUser->getId() && $cart->getStep() === $cart::STEP_EMAIL) {
            $cart->setStep($cart->nextStep($cart->getStep()));
        }
        // If user logged in & current step is address & user can not pass address early then fill address from profile
        if ($loggedUser->getId() && $cart->getStep() === $cart::STEP_ADDRESS && $cart->getAddress()->isEmpty()) {
            $cart->setAddress($loggedUser->getProfileCustomer()->getAddressBook());
        }

        return new CheckoutResult($cart, true, []);
    }

    public function cityStateAutocomplete(ShoppingCart $cart, string $byPin): CheckoutResult
    {
        $inputFilter = $this->inputFilterList->getForPin();
        $inputFilter->setData(['pin' => $byPin]);
        if ($inputFilter->isValid()) {
            $byPin = $inputFilter->getValue('pin');
            $result = new CheckoutResult($cart, true);
            foreach ((array)$this->supplierService->getPinLocation($byPin) as $key => $value) {
                $result->addSegment($key, $value);
            }
        } else {
            $result = new CheckoutResult($cart, false, $inputFilter->getMessages());
        }
        return $result;
    }

    /**
     * @param ShoppingCart $cart
     * @param User $user
     * @return OrderBundle
     * @throws \InvalidArgumentException|\RuntimeException|\Exception
     */
    public function convertCartToOrder(ShoppingCart $cart, User $user): OrderBundle
    {
        /** @var OrderPackage $package */

        //<--TODO temporary fix for email. remove after normalization of persistent cart
        if ($user && empty($cart->getEmail()) && !empty($user->getEmail())) {
            $cart->setEmail($user->getEmail());
        }
        //-->
        if (!$cart->isReadyForOrder()) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Cart required full info (isEmpty: %u, isAddressEmpty: %u, isEmailEmpty: %u, user: %u)!',
                    $cart->isEmpty(),
                    $cart->getAddress()->isEmpty(),
                    empty($cart->getEmail()),
                    $user ? $user->getId() : 0
                )
            );
        }
        $order = new OrderBundle();
        $order->setCheckoutAsGuest(false);
        if (!$user->getId()) {
            $order->setCheckoutAsGuest(true);
            $user = $this->userService->findByEmail($cart->getEmail()) ?? $user;
        }
        $address = $cart->getAddress()->toArray();
        //TODO temporary fix for old carts
        if (ctype_digit((string)$address['country'])) {
            $address['country_id'] = $address['country'];
            if ($country = $this->shippingService->loadCountry($address['country'])) {
                $address['country'] = $country->getName();
            }
        } else {
            $address['country_id'] = 101;
        }
        //-->

        $order->setCustomerAddress($address)
            ->setCreatedAt(new \DateTime())
            ->setCustomerEmail($cart->getEmail())
            ->setCustomerProfile($user->getProfileCustomer())
            ->setPaymentMethod(implode(',', $cart->getPaymentMethods()));
        $packages = [];
        $cartCurrency = $cart->getCurrency();
        foreach ($cart->getListSeller() as $seller) {
            $package = new OrderPackage();
            $package->setCurrency(empty($cartCurrency) ? MoneyService::BASE_CURRENCY : $cartCurrency);
            $package->setSupplierProfile($this->supplierService->loadSupplierProfile($seller->getSupplierId()));
            $order->addPackage($package);
            $packages[$seller->getSupplierId()] = $package;
        }
        foreach ($cart->getOffers() as $offer) {
            $delivery = $offer->getDelivery();
            $product = $offer->getProduct();
            if ($part = $this->partService->loadPart($product->getPartId())) {
                $seller = $product->getSeller();
                $package = $packages[$seller->getSupplierId()];
                $package->setDeliveryDays($delivery->getDays());
                $newItem = new OrderItem();
                $package->addItem($newItem);
                $dispatchDate = (new \DateTime())->add(new \DateInterval('P' . $seller->getDispatchDays() . 'D'));
                $newItem->setDispatchDate($dispatchDate)
                    ->setDeliveryPrice((int)$delivery->getPrice()->getAmount())
                    ->setBaseDeliveryPrice((int)$delivery->getBasePrice()->getAmount())
                    ->setQty($product->getRequestedQty())
                    ->setName($part->getName())
                    ->setPartId($product->getPartId())
                    ->setBrand($part->getBrand()->getName())
                    ->setNumber($part->getNumber())
                    ->setFamily($part->getFamily()->getName())
                    ->setPrice((int)$product->getPrice()->getAmount())
                    ->setBasePrice((int)$product->getBasePrice()->getAmount())
                    ->setOriginPrice((int)$product->getPrice()->getAmount())
                    ->setBaseOriginPrice((int)$product->getBasePrice()->getAmount())
                    ->setCost((int)$product->getCost()->getAmount())
                    ->setBaseCost((int)$product->getBaseCost()->getAmount())
                    ->setProductId($product->getId())
                    ->setDiscount((int)$offer->getDiscount()->getAmount())
                    ->setBaseDiscount(0); //Todo: Check this
            }
        }

        return $order;
    }

    public function processEmailStep(ShoppingCart $cart, string $email): CheckoutResult
    {
        $inputFilter = $this->inputFilterList->getForEmailStep();
        $inputFilter->setData(['email' => $email]);
        if (!$inputFilter->isValid()) {
            return new CheckoutResult($cart, false, $inputFilter->getMessages());
        }
        $email = $inputFilter->getValue('email');
        if (!$this->userService->isLoggedIn()) {
            $registered = $this->userService->findByEmail($email);
            if ($registered) {
                return new CheckoutResult($cart, null, [], ['email' => 'email']);
            }
        } elseif ($this->userService->getAuthIdentityUser()->getEmail() !== $email) {
            return new CheckoutResult($cart, null, [], ['email' => 'email']);
        }

        $cart->setEmail($email);
        $cart->setStep($cart::STEP_EMAIL);
        $cart->setStep($cart->nextStep($cart->getStep()));
        return new CheckoutResult($cart, true);
    }

    public function processAddressStep(ShoppingCart $cart, array $address): CheckoutResult
    {
        $inputFilter = $this->inputFilterList->getForAddressStep();
        $inputFilter->setData($address);
        if (!$inputFilter->isValid()) {
            return new CheckoutResult($cart, false, $inputFilter->getMessages());
        }
        //additional checks of PIN
        if (empty($address['pin']) || empty($this->supplierService->getPinLocation($address['pin']))) {
            return new CheckoutResult($cart, false, ['pin' => ['Unknown PIN']]);
        }
        $addressValues = $inputFilter->getValues();
        $country = $this->shippingService->loadCountry($addressValues['country']);
        //$addressValues['country'] = strtoupper($country->getName());

        $addressBook = AddressBook::fromData($addressValues);
        $cart->setAddress($addressBook);
        $cart->setStep($cart::STEP_ADDRESS);
        $cart->setStep($cart->nextStep($cart->getStep()));
        $cart->applyCurrency($country->getCurrency());
        if ($this->userService->isLoggedIn()) {
            $user = $this->userService->getAuthIdentityUser();
            if ($customer = $user->getProfileCustomer()) {
                $customer->setAddress($addressBook->toArray());
                $customer->setFirstName($addressBook->getFirstName());
                $customer->setLastName($addressBook->getLastName());
                $customer->setPin($addressBook->getPin());
                $customer->setPhone($addressBook->getPhone());
            }
            $this->userService->save($user);
        }
        return new CheckoutResult($cart, true);
    }

    public function processPaymentStep(ShoppingCart $cart, array $paymentMethods): CheckoutResult
    {
        $order = $this->convertCartToOrder($cart, $this->userService->getAuthIdentityUser());
        $paymentAvailability = $this->paymentService->getPaymentAvailability();
        $availabilityList = [];
        foreach ($paymentAvailability->getLocalProviderList($order) as $provider) {
            $availabilityList[] = $provider->getCode();
        }
        foreach ($paymentAvailability->getCrossProviderList($order) as $provider) {
            $availabilityList[] = $provider->getCode();
        }
        if (empty($paymentMethods) && \count(array_diff($paymentMethods, $availabilityList)) > 0) {
            return new CheckoutResult($cart, false, ['paymentMethod' => 'Choose payment methods.']);
        }
        $cart->setPaymentMethods($paymentMethods);
        return new CheckoutResult($cart, true);
    }

    public function getPayments(ShoppingCart $cart): array
    {
        /* @var PaymentProviderInterface $provider */
        $order = $this->convertCartToOrder($cart, $this->userService->getAuthIdentityUser());
        $availability = $this->paymentService->getPaymentAvailability();
        $result = [
            'cross' => $availability->getCrossProviderList($order)->toArray(),
            'local' => $availability->getLocalProviderList($order)->toArray(),
            'totals' => [],
        ];
        foreach ($result['cross'] + $result['local'] as $provider) {
            $order = $this->convertCartToOrder($cart, $this->userService->getAuthIdentityUser());
            $result['totals'][$provider->getCode()] = $availability->calculateTotalForProvider(
                $provider,
                $order,
                $this->getShoppingRuleProcessor()
            );
        }
        return $result;
    }

    public function getShoppingRuleProcessor(): \Closure
    {
        return function (OrderBundle $order) {
            $paymentMethods = $order->getPaymentMethods();
            $isSelectedRazorpay = $paymentMethods && \in_array('razorpay', $paymentMethods, true);
            $isAvailableCashPayment = false;

            if ($isSelectedRazorpay) {
                $availability = $this->paymentService->getPaymentAvailability();
                $localProviders = $availability->getLocalProviderList($order)->toArray();
                $isAvailableCashPayment = isset($localProviders['cash']) && !$localProviders['cash']->isDisabled();
            }

            //adds special discount of delivery for razorpay (only if present cash payment)
            if ($isSelectedRazorpay && $isAvailableCashPayment) {
                foreach ($order->getPackages() as $package) {
                    if ($package->getSupplierProfile()->getBaseCurrency() === MoneyService::BASE_CURRENCY) {
                        foreach ($package->getItems() as $item) {
                            $item->setDeliveryPrice(
                                ceil($item->getDeliveryPrice() / 100 * self::RAZORPAY_DELIVERY_DISCOUNT_KOEF) * 100
                            );
                            $item->setBaseDeliveryPrice(
                                ceil($item->getBaseDeliveryPrice() / 100 * self::RAZORPAY_DELIVERY_DISCOUNT_KOEF) * 100
                            );
                        }
                    }
                }
            }
        };
    }

    public function processOrderCreated(ShoppingCart $cart, User $forUser, array $additionalInfo = []): OrderBundle
    {
        $this->commandBus->dispatch(new NewBundleCommand($cart, $forUser, $additionalInfo));
        return $this->orderService->loadOrderBundle($cart->getLastOrderId());
    }

    public function getPaymentStack(ArrayObject $storage): array
    {
        return $storage['paymentsStack'] ?? [];
    }

    public function setPaymentStack(ArrayObject $storage, array $stack): void
    {
        $storage['paymentsStack'] = $stack;
    }

    public function lastOrderBundle(ArrayObject $storage): ?OrderBundle
    {
        try {
            $order = $this->orderService->loadOrderBundle($storage[ShoppingCart::STORAGE_KEY_ORDER_ID] ?? 0);
            unset($storage[ShoppingCart::STORAGE_KEY_ORDER_ID]);
        } catch (\Throwable $e) {
            $order = null;
        }
        return $order;
    }
}
