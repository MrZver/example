<?php

namespace Boodmo\Sales\Plugin\Transactional;

use Boodmo\Core\Service\SiteSettingService;
use Boodmo\Currency\Service\MoneyService;
use Boodmo\Email\Plugin\Transactional\AbstractPlugin;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Service\PaymentService;

class OrderConfirmationEmail extends AbstractPlugin
{
    public const TEMPLATE_ID = 'customer-orderreview';
    /**
     * @var OrderBundle
     */
    protected $order;
    /**
     * @var SiteSettingService
     */
    private $siteSettingsService;
    /**
     * @var PaymentService
     */
    private $paymentService;

    /**
     * @var MoneyService
     */
    private $moneyService;

    /**
     * OrderConfirmationEmail constructor.
     *
     * @param SiteSettingService $siteSettingsService
     * @param PaymentService     $paymentService
     * @param MoneyService       $moneyService
     */
    public function __construct(
        SiteSettingService $siteSettingsService,
        PaymentService $paymentService,
        MoneyService $moneyService
    ) {
        $this->siteSettingsService = $siteSettingsService;
        $this->paymentService      = $paymentService;
        $this->moneyService        = $moneyService;
    }

    public function getVars()
    {
        $order = $this->getOrder();

        $this->options['to'] = $order->getCustomerEmail();
        $adminEmail = $this->siteSettingsService->getSettingByPath('general/sales_form_email');
        if ($adminEmail) {
            $this->options['bcc'] = $adminEmail;
        }
        $currency = MoneyService::BASE_CURRENCY;

        $totalPrice = $this->moneyService->format(
            $this->moneyService->getMoney((string)($order->getBaseGrandTotal() / 100), MoneyService::BASE_CURRENCY),
            true
        );

        $grandTotalValues = [];
        foreach ($order->getGrandTotalList() as $currencyKey => $money) {
            $grandTotalValues[$currencyKey] = $this->moneyService->format($money, true);
        }
        if ($grandTotalValues && (count($grandTotalValues) > 1 || $currency !== array_keys($grandTotalValues)[0])) {
            $totalPrice .= ' (' . implode(' + ', $grandTotalValues) . ')';
        }

        $result               = [];
        $result['originalId'] = $order->getId();
        $result['id']         = $order->getNumber();
        $result['email']      = $order->getCustomerEmail();
        $result['total']      = $totalPrice;
        $result['name']       = $order->getCustomerAddress()['first_name'] . ' ' . $order->getCustomerAddress()['last_name'];
        $result['pin']        = $order->getCustomerAddress()['pin'];
        $result['telephone']  = $order->getCustomerAddress()['phone'];
        $result['address']    = $order->getCustomerAddress()['address'];
        $result['city']       = $order->getCustomerAddress()['city'];
        $result['state']      = $order->getCustomerAddress()['state'] ?? ''; //todo this Undefine index, Why???
        $payments             = [];
        foreach ($order->getPaymentMethods() as $code) {
            $payments[] = strip_tags($this->paymentService->getProviderByCode($code)->getLabel());
        }
        $result['method']   = implode(",\n", $payments);
        $result['packages'] = [];
        foreach ($order->getPackages() as $package) {
            $packageCurrency = $package->getCurrency();

            $temp = [];
            $temp['id']   = $package->getNumber();
            $temp['name'] = $package->getSupplierProfile()->getName();
            $temp['sold'] = $package->getSupplierProfile()->getName();
            $i = 1;
            foreach ($package->getItems() as $item) {
                if ($item->isCancelled()) {
                    continue;
                }

                $tempPart['id']     = $i++;
                $tempPart['name']   = $item->getName();
                $tempPart['number'] = $item->getNumber();
                $tempPart['brand']  = $item->getBrand();
                $tempPart['qty']    = $item->getQty();
                $tempPart['price']  = $this->moneyService->format(
                    $this->moneyService->getMoney((string)($item->getPrice() / 100), $packageCurrency),
                    true
                );
                $tempPart['delivery-item'] = $this->moneyService->format(
                    $this->moneyService->getMoney((string)($item->getDeliveryPrice() / 100), $packageCurrency),
                    true
                );
                $tempPart['subtotal'] = $this->moneyService->format(
                    $this->moneyService->getMoney((string)($item->getSubtotal() / 100), $packageCurrency),
                    true
                );

                $temp['items'][] = $tempPart;
            }
            $temp['delivery'] = $this->moneyService->format(
                $this->moneyService->getMoney((string)($package->getDeliveryTotal() / 100), $packageCurrency),
                true
            );
            $temp['package_total'] = $this->moneyService->format(
                $this->moneyService->getMoney((string)($package->getSubTotal() / 100), $packageCurrency),
                true
            );
            $result['packages'][] = $temp;
        }

        return [
            'order' => $result,
        ];
    }

    public function getOrder(): OrderBundle
    {
        return $this->order;
    }

    public function setOrder(OrderBundle $order)
    {
        $this->order = $order;

        return $this;
    }
}
