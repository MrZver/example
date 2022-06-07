<?php
/**
 * Created by PhpStorm.
 * User: bopop
 * Date: 10/18/16
 * Time: 13:27.
 */

namespace Boodmo\Sales\Model\Payment\Provider;

use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\Payment;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Model\Payment\PaymentModelInterface;
use Money\Money;
use Money\Currency as MoneyCurrency;
use PayPal\Api\{
    Address, BillingInfo, Cost, Currency, Invoice, InvoiceAddress, InvoiceItem, MerchantInfo,
    PaymentTerm, Phone, Search, ShippingInfo, ShippingCost
};
use \PayPal\Rest\ApiContext;
use \PayPal\Auth\OAuthTokenCredential;
use PayPal\Exception\PayPalConnectionException;
use Zend\Http\Request;
use Zend\Json\Json;

class PayPalProvider extends AbstractPaymentProvider
{
    const CODE = 'paypal';
    const VIEW_TEMPLATE = 'sales/payment/form/paypal';
    const PAID_STATUS = 'PAID';
    protected $name = 'PayPal';

    protected $prepaid = true;
    protected $zohoPaymentAccount = '458850000000072047';
    protected $baseCurrency = 'USD';
    protected $apiKey = '';
    protected $secretKey = '';
    protected $merchantEmail = '';
    protected $merchantFirstName = '';
    protected $merchantLastName = '';
    protected $company = '';
    protected $merchantAddress = '';
    protected $merchantCity = '';
    protected $merchantState = '';
    protected $merchantPostal = '';
    protected $merchantCountryCode = '';
    protected $kf = 1;
    protected $invoice = null;
    protected $apiContext = null;
    protected $restCall = [ // for testing
        'search' => null,
        'create' => null,
        'send' => null,
        'get' => null,
    ];

    public function getRestCall($key)
    {
        return $this->restCall[$key];
    }

    public function setRestCall($key, $value)
    {
        $this->restCall[$key] = $value;
        return $this;
    }

    public function setInvoice($invoice)
    {
        $this->invoice = $invoice;
        return $this;
    }

    /**
     * @param OrderBill $orderBill
     * @return null|Invoice
     * @throws \Exception
     */
    public function getInvoice(OrderBill $orderBill)
    {
        $apiContext = $this->getApiContext();

        $number = $this->getInvoiceNumber($orderBill);
        $invoice = new Invoice();
        $this->invoice = $invoice->search((new Search())->setNumber($number), $apiContext, $this->restCall['search'])->getInvoices()[0] ?? $invoice;
        if ($this->invoice->getStatus() === self::PAID_STATUS) {
            throw new \Exception(sprintf('Invoice already paid (bill id: %s)', $orderBill->getId()));
        }
        $this->fillingInvoice($this->invoice, $orderBill);
        $countPaypalPayments = $orderBill->getPayments()
            ->filter(function (Payment $payment) {
                return $this->getCode() === $payment->getPaymentMethod();
            })->count();
        $invoiceItems = [];
        $dc = 0;
        $currency = $this->getBaseCurrency();
        if ($countPaypalPayments) {
            $this->invoice->setItems([(new InvoiceItem())->setName("Additional payment for Order #".$orderBill->getBundle()->getId())
                ->setQuantity(1)->setUnitPrice((new Currency())->setCurrency($currency)->setValue($orderBill->getPaymentDue() / 100))]);
            $this->saveInvoice($this->invoice);
            return $this->invoice;
        }

        [$grandTotalUSD, $itemTotalUSD, $items] = $this->calculateItemsTotal($orderBill->getBundle());
        foreach ($items as $i => $item) {
            $invoiceItems[$i] = new InvoiceItem();
            $invoiceItems[$i]->setName($item['name'])->setQuantity($item['qty'])->setUnitPrice(new Currency());
            $invoiceItems[$i]->getUnitPrice()->setCurrency($currency)->setValue($item['price']);
        }
        $discountUSD = ($grandTotalUSD * ($dc / 100));
        $servicesUSD = $grandTotalUSD - $itemTotalUSD;
        //  Services item
        $index = count($invoiceItems);
        $invoiceItems[$index] = new InvoiceItem();
        $invoiceItems[$index]->setName('Logistic services, Customs duty, Commission')->setQuantity(1)
            ->setUnitPrice(new Currency());
        $invoiceItems[$index]->getUnitPrice()->setCurrency($currency)->setValue($servicesUSD);

        $this->invoice->setItems($invoiceItems);

        if ($discountUSD > 0) {
            $cost = new Cost();
            $cost->setAmount(new Currency());
            $cost->getAmount()->setCurrency($currency)->setValue($discountUSD);
            $this->invoice->setDiscount($cost);
        }

        $this->saveInvoice($this->invoice);
        return $this->invoice;
    }

    public function fillingInvoice(Invoice $invoice, OrderBill $orderBill): Invoice
    {
        $bundle = $orderBill->getBundle();
        $data = $bundle->getCustomerAddress();
        $data['name'] = $data['first_name'] . ' ' . $data['last_name'];
        $data['email'] = $bundle->getCustomerEmail();
        $data['number'] = $this->getInvoiceNumber($orderBill);

        $merchantInfo = new MerchantInfo();
        $billingInfo  = new BillingInfo();
        $shippingInfo  = new ShippingInfo();
        $merchantPhone = new Phone();
        $shippingPhone = new Phone();
        $merchantInvoiceAddress = new InvoiceAddress();
        $billingShippingInvoiceAddress = new InvoiceAddress();
        $paymentTerm= new PaymentTerm();

        $merchantPhone->setCountryCode("91")->setNationalNumber("5032141716");
        $shippingPhone->setCountryCode("91")->setNationalNumber($data['phone']);
        $paymentTerm->setTermType("NET_90");

        $merchantInvoiceAddress->setLine1($this->getMerchantAddress())
            ->setCity($this->getMerchantCity())
            ->setState($this->getMerchantState())
            ->setPostalCode($this->getMerchantPostal())
            ->setCountryCode($this->getMerchantCountryCode());

        $billingShippingInvoiceAddress->setLine1($data['address'])
            ->setCity($data['city'])
            ->setState($data['state'])
            ->setPostalCode($data['pin'])
            ->setCountryCode('IN'); // todo We saved in $data['country'] Full name for Country. Need short!

        $merchantInfo->setEmail($this->getMerchantEmail())
            ->setFirstName($this->getMerchantFirstName())
            ->setLastName($this->getMerchantLastName())
            ->setBusinessName($this->getCompany())
            ->setPhone($merchantPhone)
            ->setAddress($merchantInvoiceAddress);

        $billingInfo->setEmail($data['email'])
            ->setBusinessName($data['name'])
            ->setAddress($billingShippingInvoiceAddress);

        $shippingInfo->setFirstName($data['first_name'])
            ->setLastName($data['last_name'])
            ->setPhone($shippingPhone)
            ->setAddress($billingShippingInvoiceAddress);

        $invoice->setMerchantInfo($merchantInfo)
            ->setBillingInfo([$billingInfo])
            ->setNote("Order #" . $data['number'] . " placed on " . $bundle->getCreatedAt()->format('d F, Y'))
            ->setPaymentTerm($paymentTerm)
            ->setShippingInfo($shippingInfo)
            ->setReference($orderBill->getId())
            ->setNumber($data['number'])
            ->setLogoUrl('https://boodmo.com/img/logo.png');

        return $invoice;
    }

    public function getInvoiceNumber(OrderBill $orderBill): string
    {
        return $orderBill->getBundle()->getId() . '/' . $orderBill->getUpdatedAt()->format('mdHi');
    }

    /**
     * @param Invoice $invoice
     * @throws \Exception
     */
    public function saveInvoice(Invoice $invoice): void
    {
        $apiContext = $this->getApiContext();

        try {
            if ($invoice->getId()) {
                $invoice->update($apiContext, $this->restCall['create']);
            } else {
                $invoice->create($apiContext, $this->restCall['create']);
                $invoice->send($apiContext, $this->restCall['send']);
            }
        } catch (PayPalConnectionException $e) {
            throw new \Exception('PayPal API error: '.$e->getData(), $e->getCode(), $e);
        }
    }

    public function authorize(PaymentModelInterface $paymentService, OrderBill $orderBill): array
    {
        parent::authorize($paymentService, $orderBill);
        $bundle = $orderBill->getBundle();
        $invoice = $this->getInvoice($orderBill);
        $external_link = $invoice->getMetadata()->getPayerViewUrl();
        if ($external_link === null) {
            $invoice = $invoice->get($invoice->getId(), $this->getApiContext());
            $external_link = $invoice->getMetadata()->getPayerViewUrl();
        }
        return [
            'number'        => $this->getInvoiceNumber($orderBill),
            'email'         => $bundle->getCustomerEmail(),
            'amount'        => $orderBill->getPaymentDue(),
            'bill_id'       => $orderBill->getId(),
            'external_link' => $external_link,
        ];
    }

    public function capture(PaymentModelInterface $paymentService, Request $request): void
    {
        $payload = Json::decode($request->getContent(), Json::TYPE_ARRAY) ?? [];
        if (!empty($invoice = $payload['resource']['invoice'] ?? [])) {
            $paymentService->markAsPaid(
                $invoice['reference'],
                $invoice['id'],
                $invoice['paidAmount']['paypal']['value'] * 100,
                $invoice
            );
        }
    }

    /**
     * @param OrderBundle $bundle
     * @return array
     */
    private function calculateItemsTotal(OrderBundle $bundle) : array
    {
        $allItems = $bundle->getPackagesWithCurrency($this->getBaseCurrency())->map(function (OrderPackage $package) {
            return $package->getActiveItems()->toArray() ?? [];
        });
        if (count($allItems)) {
            $allItems = array_merge(...$allItems);
        }

        //  Prepare kf for calculating prices
        $kf = $this->getKf();
        $currency = new MoneyCurrency($this->getBaseCurrency());
        $grandTotalUSD = new Money(0, $currency);
        $itemTotalUSD = new Money(0, $currency);
        $items = [];

        foreach ($allItems as $i => $item) {
            $price = new Money($item->getPrice(), $currency);
            $deliveryPrice = new Money($item->getDeliveryPrice(), $currency);
            $countItems = $item->getQty();

            $itemPriceUSD = $price->divide($kf);
            $itemTotalUSD = $itemTotalUSD->add($itemPriceUSD->multiply($countItems));
            $grandTotalUSD = $grandTotalUSD->add($price->add($deliveryPrice)->multiply($countItems));

            $items[$i] = [
                'qty' => $item->getQty(),
                'price' => $itemPriceUSD->getAmount() / 100,
                'name' => $item->getName()
            ];
        }

        return [$grandTotalUSD->getAmount() / 100, $itemTotalUSD->getAmount() / 100, $items];
    }

    public function getApiContext(): ApiContext
    {
        if (!$this->apiContext) {
            $this->apiContext = new ApiContext(new OAuthTokenCredential($this->getApiKey(), $this->getSecretKey()));
            $this->apiContext->setConfig(['mode' => $this->getLiveMode() ? 'live' : 'sandbox']);
        }
        return $this->apiContext;
    }

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     *
     * @return $this
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    /**
     * @param string $secretKey
     *
     * @return $this
     */
    public function setSecretKey($secretKey)
    {
        $this->secretKey = $secretKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getMerchantEmail(): string
    {
        return $this->merchantEmail;
    }

    /**
     * @param string $merchantEmail
     *
     * @return $this
     */
    public function setMerchantEmail($merchantEmail)
    {
        $this->merchantEmail = $merchantEmail;
        return $this;
    }

    /**
     * @return string
     */
    public function getMerchantFirstName(): string
    {
        return $this->merchantFirstName;
    }

    /**
     * @param string $merchantFirstName
     *
     * @return $this
     */
    public function setMerchantFirstName($merchantFirstName)
    {
        $this->merchantFirstName = $merchantFirstName;
        return $this;
    }

    /**
     * @return string
     */
    public function getMerchantLastName(): string
    {
        return $this->merchantLastName;
    }

    /**
     * @param string $merchantLastName
     *
     * @return $this
     */
    public function setMerchantLastName($merchantLastName)
    {
        $this->merchantLastName = $merchantLastName;
        return $this;
    }

    /**
     * @return string
     */
    public function getCompany(): string
    {
        return $this->company;
    }

    /**
     * @param string $company
     *
     * @return $this
     */
    public function setCompany($company)
    {
        $this->company = $company;
        return $this;
    }

    /**
     * @return string
     */
    public function getMerchantAddress(): string
    {
        return $this->merchantAddress;
    }

    /**
     * @param string $merchantAddress
     *
     * @return $this
     */
    public function setMerchantAddress($merchantAddress)
    {
        $this->merchantAddress = $merchantAddress;
        return $this;
    }

    /**
     * @return string
     */
    public function getMerchantCity(): string
    {
        return $this->merchantCity;
    }

    /**
     * @param string $merchantCity
     *
     * @return $this
     */
    public function setMerchantCity($merchantCity)
    {
        $this->merchantCity = $merchantCity;
        return $this;
    }

    /**
     * @return string
     */
    public function getMerchantState(): string
    {
        return $this->merchantState;
    }

    /**
     * @param string $merchantState
     *
     * @return $this
     */
    public function setMerchantState($merchantState)
    {
        $this->merchantState = $merchantState;
        return $this;
    }

    /**
     * @return string
     */
    public function getMerchantPostal(): string
    {
        return $this->merchantPostal;
    }

    /**
     * @param string $merchantPostal
     *
     * @return $this
     */
    public function setMerchantPostal($merchantPostal)
    {
        $this->merchantPostal = $merchantPostal;
        return $this;
    }

    /**
     * @return string
     */
    public function getMerchantCountryCode(): string
    {
        return $this->merchantCountryCode;
    }

    /**
     * @param string $merchantCountryCode
     *
     * @return $this
     */
    public function setMerchantCountryCode($merchantCountryCode)
    {
        $this->merchantCountryCode = $merchantCountryCode;
        return $this;
    }

    /**
     * @return float
     */
    public function getKf(): float
    {
        return $this->kf;
    }

    /**
     * @param float $kf
     *
     * @return $this
     */
    public function setKf(float $kf)
    {
        $this->kf = $kf;
        return $this;
    }
}
