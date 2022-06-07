<?php
/**
 * Created by PhpStorm.
 * User: Shandy
 * Date: 01.07.2017
 * Time: 16:16
 */

namespace Boodmo\Sales\Model\Payment;

use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderPackage;
use Doctrine\Common\Collections\ArrayCollection;
use Money\Currency;
use Money\Money;

class PaymentAvailability
{
    /**
     * @var ArrayCollection|PaymentProviderInterface[]
     */
    private $providers;

    /**
     * PaymentAvailability constructor.
     *
     * @param ArrayCollection $providers
     */
    public function __construct(ArrayCollection $providers)
    {
        $this->providers = $providers;
    }

    public function getLocalProviderList(OrderBundle $order): ArrayCollection
    {
        $localPackages = $order->getPackages()->filter(function (OrderPackage $package) {
            return $package->getSupplierProfile()->getBaseCurrency() === MoneyService::BASE_CURRENCY;
        });
        return $this->providers->filter(function (PaymentProviderInterface $provider) {
            return $provider->isActive();
        })->filter(function (PaymentProviderInterface $provider) use ($localPackages) {
            return $provider->getBaseCurrency() === MoneyService::BASE_CURRENCY && !$localPackages->isEmpty();
        })->map(function (PaymentProviderInterface $provider) use ($localPackages) {
            if (!$provider->isPrepaid()) {
                $isCashActive = $localPackages->forAll(function (int $k, OrderPackage $package) {
                    return (bool) $package->getSupplierProfile()->isCashDelivery();
                });
                $provider->setDisabled(!$isCashActive);
            }
            return $provider;
        });
    }

    public function getCrossProviderList(OrderBundle $order): ArrayCollection
    {
        //TODO without refactoring can work only with USD
        $crossPackages = $order->getPackages()->filter(function (OrderPackage $package) {
            return $package->getSupplierProfile()->getBaseCurrency() === 'USD';
        });
        return $this->providers->filter(function (PaymentProviderInterface $provider) {
            return $provider->isActive();
        })->filter(function (PaymentProviderInterface $provider) use ($crossPackages) {
            return $provider->getBaseCurrency() === 'USD' && !$crossPackages->isEmpty();
        });
    }

    public function calculateTotalForProvider(
        PaymentProviderInterface $provider,
        OrderBundle $order,
        \Closure $shoppingRuleProcessor
    ): Money {
        $shoppingRuleProcessor($order);
        $currency = new Currency($provider->getBaseCurrency());
        $total = new Money(0, $currency);
        foreach ($order->getPackages() as $package) {
            if ($package->getCurrency() === $currency->getCode()) {
                $total = $total->add(new Money($package->getGrandTotal(), $currency));
            }
        }
        return $total;
    }
}
