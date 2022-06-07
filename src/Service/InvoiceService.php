<?php

namespace Boodmo\Sales\Service;

use Boodmo\Catalog\Entity\Part;
use Boodmo\Currency\Service\MoneyService;
use Boodmo\Media\Service\MediaService;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\User\Entity\Address;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\User\Entity\UserProfile\Supplier;
use Boodmo\User\Service\AddressService;
use Doctrine\ORM\EntityManager;
use Zend\Http\Client;
use Zend\Http\Request;
use Boodmo\Shipping\Entity\ShippingBox;

class InvoiceService
{
    public const SUPPLIER_IDS_WITH_BLOCKED_INVOICES = [271, 272, 278];
    public const BLOCKED_INVOICE_MESSAGE = 'INVOICE SHOULD NOT BE GENERATED FOR THIS SUPPLIER';

    private $config;

    /**
     * @var EntityManager
     */
    private $_em;

    /**
     * @var MediaService
     */
    private $mediaService;

    /**
     * @var AddressService
     */
    private $addressService;

    /**
     * @var TaxesService
     */
    private $taxesService;

    /**
     * @var MoneyService
     */
    private $moneyService;

    private $tryCount = 0;

    private $fromQueue = false;

    public const NUMBER_OF_TRY = 2;

    public const INVOICE_STATES = [
        "andaman & nicobar islands" => "AN",
        "andhra pradesh" => "AD",
        "arunachal pradesh" => "AR",
        "assam" => "AS",
        "bihar" => "BH",
        "chandigarh" => "CH",
        "chhattisgarh" => "CT",
        "dadra & nagar haveli" => "DN",
        "daman & diu" => "DD",
        "delhi" => "DL",
        "goa" => "GA",
        "gujarat" => "GJ",
        "haryana" => "HR",
        "himachal pradesh" => "HP",
        "jammu & kashmir" => "JK",
        "jharkhand" => "JH",
        "karnataka" => "KA",
        "kerala" => "KL",
        "lakshadweep" => "LD",
        "madhya pradesh" => "MP",
        "maharashtra" => "MH",
        "manipur" => "MN",
        "meghalaya" => "ME",
        "mizoram" => "MI",
        "nagaland" => "NL",
        "odisha" => "OR",
        "puducherry" => "PY",
        "punjab" => "PB",
        "rajasthan" => "RJ",
        "sikkim" => "SK",
        "tamil nadu" => "TN",
        "telangana" => "TS",
        "tripura" => "TR",
        "uttar pradesh" => "UP",
        "uttarakhand" => "UT",
        "west bengal" => "WB",
    ];

    /**
     * InvoiceService constructor.
     * @param $config
     * @param $doctrineManager
     * @param MediaService $mediaService
     * @param AddressService $addressService
     * @param TaxesService $taxesService
     * @param MoneyService $moneyService
     */
    public function __construct(
        $config,
        $doctrineManager,
        MediaService $mediaService,
        AddressService $addressService,
        TaxesService $taxesService,
        MoneyService $moneyService
    ) {
        $this->config = $config;
        $this->_em = $doctrineManager;
        $this->mediaService = $mediaService;
        $this->addressService = $addressService;
        $this->taxesService = $taxesService;
        $this->moneyService = $moneyService;

        $this->moneyService->disableRoundWithoutPenny();
    }

    public function getInvoiceSnapshot(OrderPackage $package, $fromQueue = false)
    {
        $this->fromQueue = $fromQueue;
        return $this->provideInvoiceData($package);
    }

    public function getInvoiceDocByPackageId($packageId)
    {
        $package = $this->_em->getRepository(OrderPackage::class)->findOneBy(['id' => $packageId]);
        $snapshots = [];
        if ($package instanceof OrderPackage) {
            if ($this->isBlockedInvoiceGeneration($package)) {
                return self::BLOCKED_INVOICE_MESSAGE;
            }
            $invoiceSnapshot = $package->getInvoiceSnapshot();
            $invoiceSnapshot = $this->addSignatureToSnapshot($invoiceSnapshot, $package);
            $invoiceSnapshot = $this->addCustomDataToSnapshot($invoiceSnapshot, $package);

            $snapshots[] = $invoiceSnapshot["data"];
            $invoiceSnapshot["data"] = [
                "invoices" => $snapshots,
            ];
            $invoiceSnapshot["templateName"] = $this->config['docmosis']['templateName'];
            $invoiceSnapshot["accessKey"] = $this->config['docmosis']['accessKey'];

            return $this->renderInvoiceFile($invoiceSnapshot);
        }
    }

    public function getInvoiceDocByShippingBoxId(string $shippingBoxId, bool $saveToDb = false)
    {
        $shippingBox = $this->_em->getRepository(ShippingBox::class)->findOneBy(['id' => $shippingBoxId]);
        $snapshots = [];
        foreach ($shippingBox->getPackages() as $package) {
            if ($package instanceof OrderPackage) {
                if ($this->isBlockedInvoiceGeneration($package)) {
                    return self::BLOCKED_INVOICE_MESSAGE;
                }
                $invoiceSnapshot = $package->getInvoiceSnapshot();
                if (!$invoiceSnapshot) {
                    $invoiceSnapshot = $this->getInvoiceSnapshot($package);
                }
                $invoiceSnapshot = $this->addSignatureToSnapshot($invoiceSnapshot, $package);
                $invoiceSnapshot = $this->addCustomDataToSnapshot($invoiceSnapshot, $package);
                $snapshots[] = $invoiceSnapshot["data"];

                if ($saveToDb === true) {
                    $package->setInvoiceSnapshot($invoiceSnapshot);
                    $this->_em->persist($package);
                    $this->_em->flush();
                }
            }
        }

        $data = [
            "templateName" => $this->config['docmosis']['templateName'],
            "outputName" => str_replace(['/', '|', '-'], '_', 'Boodmo_Invoice_' . $shippingBoxId . "/C") . '.doc',
            "outputFormat" => "doc",
            "accessKey" => $this->config['docmosis']['accessKey'],
            "data" => [
                "invoices" => $snapshots,
            ],
        ];

        return $saveToDb ? $snapshots : $this->renderInvoiceFile($data);
    }

    public function getPickListDoc(array $items = [])
    {
        $snapshot = [
            'templateName' => $this->config['docmosis']['picklistTemplateName'],
            'outputName' => 'picklist-'.time().'.docx',
            'outputFormat' => 'docx',
            'accessKey' => $this->config['docmosis']['accessKey'],
            'data' => [
                'items' => [],
            ],
        ];

        if ($items) {
            $supplierProfile = $items[0]['package']['supplierProfile'];
            $snapshot['data']['supplier_name'] = $supplierProfile['name'] ?? '';
            if ($supplierProfile['addresses']) {
                $address = $supplierProfile['addresses'][0];
                $snapshot['data']['supplier_address'] = $address['address'] ?? '';
                $snapshot['data']['supplier_city'] = $address['city'] ?? '';
                $snapshot['data']['supplier_state'] = $address['state'] ?? '';
            }
            $date = new \DateTime();
            $snapshot['data']['date_created'] = $date->format('d/m/Y H:i:s');
        }

        foreach ($items as $orderItem) {
            $snapshot['data']['items'][] = [
                'bundle'        => $orderItem['package']['bundle']['id'],
                'brand'         => $orderItem['brand'],
                'part_number'   => $orderItem['number'],
                'part_name'     => $orderItem['name'],
                'Items_count'   => $orderItem['qty'],
            ];
        }
        
        return $this->renderInvoiceFile($snapshot);
    }

    private function addSignatureToSnapshot($invoiceSnapshot, $package)
    {
        $supplier = $this->getSupplierByPackage($package);
        $signature = $supplier->getSignature();
        if ($signature) {
            $path = $this->mediaService->absoluteFilename($signature);
            $data = file_get_contents($path);
            $base64 = 'image:base64:' . base64_encode($data);
            $invoiceSnapshot['data']['signature'] = $base64;
        }
        return $invoiceSnapshot;
    }

    private function addCustomDataToSnapshot($invoiceSnapshot, $package)
    {
        $currency = $package->getCurrency();
        $invoiceSnapshot["data"]["currency"] = $currency;
        $invoiceSnapshot["data"]["customer_state_code"] = self::INVOICE_STATES[strtolower($invoiceSnapshot["data"]["customer_state"])];
        $taxes = ['IGST', 'SGST', 'CGST'];
        $netAmountSubtotal = 0;
        foreach ($invoiceSnapshot["data"]["items"] as $key => $item) {
            $amount = (float)str_replace(',', '', $invoiceSnapshot['data']['items'][$key]['amount']);
            $netAmountSubtotal += $amount;
            foreach ($taxes as $taxname) {
                $invoiceSnapshot["data"][$taxname."_subtotal"] = 0;
                $invoiceSnapshot["data"]["items"][$key]["{$taxname}_tax_rate"] = 0;
                $invoiceSnapshot["data"]["items"][$key]["{$taxname}_tax_amount"] = 0;
                $invoiceSnapshot["data"]["items"][$key]["{$taxname}_subtotal"] = 0;
                foreach ($invoiceSnapshot["data"]["taxes"] as $tax) {
                    if (strtoupper($tax["name"]) == $taxname) {
                        $invoiceSnapshot["data"]["items"][$key]["{$taxname}_tax_rate"] = $tax["rate"];
                        $invoiceSnapshot["data"]["items"][$key]["{$taxname}_tax_amount"] = $this->moneyService->format($this->moneyService->getMoney(round($amount * $tax["rate"] / 100, 2), $currency));
                        break;
                    }
                }
            }
            $item = $invoiceSnapshot["data"]["items"][$key];
            if (empty($invoiceSnapshot["data"]["items"][$key]["total_amount"])) {
                $invoiceSnapshot["data"]["items"][$key]["total_amount"] = $this->moneyService->format($this->moneyService->getMoney(round($amount * (1 + $item["IGST_tax_rate"]/100 + $item["SGST_tax_rate"]/100 + $item["CGST_tax_rate"]/100), 2), $currency));
            }
        }
        foreach ($invoiceSnapshot["data"]["taxes"] as $tax) {
            $invoiceSnapshot["data"][strtoupper($tax["name"])."_subtotal"] = $tax["amount"];
        }

        $numberFormatter   = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
        $grand_total = (float)str_replace(',', '', $invoiceSnapshot['data']['grand_total']);
        $delivery_charges = (float)str_replace(',', '', $invoiceSnapshot['data']['delivery_charges']);
        $subtotal = round($grand_total - $delivery_charges, 2);
        $subtotalVerbal = $numberFormatter->format($subtotal);
        $subtotal = $this->moneyService->format($this->moneyService->getMoney($subtotal, $currency));
        $invoiceSnapshot['data']["subtotal"] = $subtotal;
        $invoiceSnapshot['data']["subtotal_words"] = $subtotalVerbal;
        $invoiceSnapshot['data']["net_amount_subtotal"] = $this->moneyService->format(
            $this->moneyService->getMoney($netAmountSubtotal, $currency)
        );
        $invoiceSnapshot['data']['customer_address'] = str_replace(
            "'",
            '',
            $invoiceSnapshot['data']['customer_address']
        );

        return $invoiceSnapshot;
    }

    public function getFacilitationInvoiceDocByPackageId($packageId)
    {
        $package = $this->_em->getRepository(OrderPackage::class)->findOneBy(['id' => $packageId]);
        if ($package instanceof OrderPackage) {
            $invoiceSnapshot = $this->getFacilitationInvoiceSnapshot($package);
            return $this->renderInvoiceFile($invoiceSnapshot);
        }
    }

    public function getSupplierInvoiceDoc($packageId, $type)
    {
        $package = $this->_em->getRepository(OrderPackage::class)->findOneBy(['id' => $packageId]);
        if ($package instanceof OrderPackage) {
            if ($package->getCostTotal() == 0) {
                return 'INVOICE CAN NOT BE DOWNLOADED BECAUSE TOTAL = 0';
            }
            if ($type == 'B') {
                $snapshots = [];
                if ($this->isBlockedInvoiceGeneration($package)) {
                    return self::BLOCKED_INVOICE_MESSAGE;
                }
                $invoiceSnapshot = $package->getInvoiceSnapshot();
                if (!$invoiceSnapshot) {
                    $invoiceSnapshot = $this->getInvoiceSnapshot($package);
                }
                $invoiceSnapshot = $this->addSignatureToSnapshot($invoiceSnapshot, $package);
                $invoiceSnapshot = $this->addCustomDataToSnapshot($invoiceSnapshot, $package);

                $snapshots[] = $invoiceSnapshot["data"];
                $invoiceSnapshot["data"] = [
                    "invoices" => $snapshots,
                ];
            } elseif ($type == 'S') {
                $invoiceSnapshot = $this->getFacilitationInvoiceSnapshot($package);
            } else {
                return 'INCORRECT TYPE';
            }

            $invoiceSnapshot["templateName"] = $this->config['docmosis']["supplier{$type}TemplateName"];
            $invoiceSnapshot["outputName"] = "supplier-{$type}-".time().".doc";

            return $this->renderInvoiceFile($invoiceSnapshot);
        }
    }

    private function getFacilitationInvoiceSnapshot(OrderPackage $package)
    {
        $invoiceSnapshot = $package->getInvoiceSnapshot();
        $numberFormatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
        $supplierProfile   = $package->getSupplierProfile();
        $currency = $package->getCurrency();

        $invoiceSnapshot['templateName'] = $this->config['docmosis']['facilitationTemplateName'];
        $invoiceSnapshot['accessKey'] = $this->config['docmosis']['accessKey'];
        $invoiceSnapshot['outputName'] = str_replace(['/', '|', '-'], '_', 'Boodmo_Facilitation_Invoice_'
                . $package->getFacilitationInvoiceNumber()) . '.doc';
        $invoiceSnapshot['data']['invoice_number'] = $package->getFacilitationInvoiceNumber();
        $invoiceSnapshot['data']['invoice_date'] = date("d M Y");
        $invoiceSnapshot['data']['supplier_email'] = $supplierProfile->getEmail();
        $invoiceSnapshot['data']['supplier_phone'] = $supplierProfile->getPhone();
        $invoiceSnapshot['data']['supplier_pan'] = $supplierProfile->getPan();
        $invoiceSnapshot['data']['supplier_st'] = $supplierProfile->getSt();
        $invoiceSnapshot['data']['supplier_cin'] = $supplierProfile->getCin();

        $amount = $package->getGrandTotal()/100 - $package->getCostTotal()/100;
        $percent_05 = $amount * 0.005;
        $percent_14 = $amount * 0.14;
        $invoiceSnapshot['data']['qty'] = 1;
        $invoiceSnapshot['data']['amount'] = $this->moneyService->format(
            $this->moneyService->getMoney($amount, $currency)
        );
        $invoiceSnapshot['data']['percent_05'] = $this->moneyService->format(
            $this->moneyService->getMoney($percent_05, $currency)
        );
        $invoiceSnapshot['data']['percent_14'] = $this->moneyService->format(
            $this->moneyService->getMoney($percent_14, $currency)
        );

        $total = $amount + $percent_05 + $percent_05 + $percent_14;
        $roundInfo = $this->roundWithInfo($total);
        $roundedTotal = $roundInfo['result'];
        $grandTotalVerbal = $numberFormatter->format($roundedTotal);
        $grandTotal = $this->moneyService->format($this->moneyService->getMoney($roundedTotal, $currency));

        $invoiceSnapshot['data']['roundoff_sign'] = $roundInfo['sign'];
        $invoiceSnapshot['data']['roundoff'] = $this->moneyService->format(
            $this->moneyService->getMoney($roundInfo['diff'], $currency)
        );
        $invoiceSnapshot['data']['grand_total'] = $grandTotal;
        $invoiceSnapshot['data']['grand_total_words'] = $grandTotalVerbal;
        return $invoiceSnapshot;
    }

    private function provideInvoiceData(OrderPackage $package)
    {
        /** @var OrderItem $packageItem */
        /** @var Address $addresses */

        $now = date("d/m/Y");
        $supplierProfile   = $this->getSupplierByPackage($package);
        $bundle            = $package->getBundle();
        $shippingAddresses = $this->addressService->getSupplierAddress($supplierProfile, 'shipping');
        $customerAddress   = $bundle->getCustomerAddress();
        $currency          = $package->getCurrency();
        $numberFormatter   = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
        $deliveryTotal     = $package->getDeliveryTotal() / 100;

        $items     = [];
        $taxes     = [];
        $i         = 1;
//        $taxSum    = 0;
        $amountSum = 0;
        $taxesNames = ['igst', 'sgst', 'cgst'];
        $maxTaxes = ['igst' => 0, 'sgst' => 0, 'cgst' => 0];

        $sourceState = $package->getSourceState();
        $destinationState = $package->getDestinationState();

        foreach ($package->getItems() as $packageItem) {
            $itemTaxes = ['igst' => 0, 'sgst' => 0, 'cgst' => 0];
            if (!$packageItem->isAnyCancelled()) {
                /** @var Part $part */
                $part = $this->_em->getRepository(Part::class)->findOneBy(['id' => $packageItem->getPartId()]);
                $itemTaxPr   = $this->taxesService->getTaxesFor($part ? $part->getFamily() : null);
                $itemTaxPr   = $itemTaxPr ? $itemTaxPr[0] : null;
                $acceptedBid = $packageItem->getItemAcceptedBid();
                if (!empty($acceptedBid)) {
                    if (!empty($acceptedBid->getGst())) {
                        $itemTaxPr['igst'] = $acceptedBid->getGst();
                    }
                }

                if ($currency == 'INR') {
                    if ($sourceState == $destinationState) { // sGST, cGST
                        $itemTaxes['sgst'] = $itemTaxPr['sgst'];
                        $itemTaxes['cgst'] = $itemTaxPr['cgst'];
                    } else { // iGST
                        $itemTaxes['igst'] = $itemTaxPr['igst'];
                    }
                }
                foreach ($taxesNames as $taxName) {
                    if ($itemTaxes[$taxName] > $maxTaxes[$taxName]) {
                        $maxTaxes[$taxName] = $itemTaxes[$taxName];
                    }
                }
            }
        }

        foreach ($package->getItems() as $packageItem) {
            if (!$packageItem->isAnyCancelled()) {
                $item = [];

                /** @var Part $part */
                $part       = $this->_em->getRepository(Part::class)->findOneBy(['id' => $packageItem->getPartId()]);
                $itemTaxPr  = $this->taxesService->getTaxesFor($part ? $part->getFamily() : null);
                $itemTaxPr  = $itemTaxPr ? $itemTaxPr[0] : null;
                $acceptedBid = $packageItem->getItemAcceptedBid();
                if (!empty($acceptedBid)) {
                    if (!empty($acceptedBid->getGst())) {
                        $itemTaxPr['igst'] = $acceptedBid->getGst();
                    }
                }
                $taxPercent = 0;

                if ($itemTaxPr) {
                    if ($sourceState == $destinationState) { // sGST, cGST
                        $taxPercent = $maxTaxes['sgst'] + $maxTaxes['cgst'];
                    } else { // iGST
                        $taxPercent = $maxTaxes['igst'];
                    }
                }
                $packageItemPrice = $packageItem->getPrice() / 100;
                $item['total_amount']  = $this->moneyService->format($this->moneyService->getMoney($packageItemPrice * $packageItem->getQty(), $currency));
                $itemTax = 0;
                if ($currency == 'INR') {
                    $itemTax    = round($packageItemPrice * (1 - (1 / (1 + $taxPercent/100))), 2); // old
                }
                $priceWOTax = $packageItemPrice - $itemTax;
                $amount     = $priceWOTax * $packageItem->getQty();

                $item['si']           = $i;
                $item['part_number']  = $packageItem->getNumber();
                $item['part_name']    = $packageItem->getName();
                $item['qty']          = $packageItem->getQty();
                $item['price_wo_tax'] = $this->moneyService->format(
                    $this->moneyService->getMoney($priceWOTax, $currency)
                );
                $item['amount']       = $this->moneyService->format($this->moneyService->getMoney($amount, $currency));
                $item['tax_rate']     = $taxPercent;

                $amountSum += $amount;
                $items[]   = $item;
                $i++;

                if ($currency == 'INR') {
                    if ($sourceState == $destinationState) { // sGST, cGST
                        $itemTaxes = [
                            [
                                'name' => 'sGST',
                                'rate' => $maxTaxes['sgst'],
                                'amount' => round($maxTaxes['sgst'] * ($amount / 100), 2),
                            ],
                            [
                                'name' => 'cGST',
                                'rate' => $maxTaxes['cgst'],
                                'amount' => round($maxTaxes['cgst'] * ($amount / 100), 2),
                            ],
                        ];
                    } else { // iGST
                        $itemTaxes = [
                            [
                                'name' => 'iGST',
                                'rate' => $maxTaxes['igst'],
                                'amount' => round($maxTaxes['igst'] * ($amount / 100), 2),
                            ],
                        ];
                    }

                    $taxes = array_merge($taxes, $itemTaxes);
                }
            }
        }

        $total = $amountSum + $deliveryTotal;

        foreach ($taxes as $key => $val) {
            if ($val['rate'] == 0 || is_null($val['rate'])) {
                unset($taxes[$key]);
            } else {
                $total += $val['amount'];
            }
        }

        $groupedTaxes = [];
        foreach (array_values($taxes) as $tax) {
            $key = $tax['name']/* . '_' . $tax['rate']*/;
            if (array_key_exists($key, $groupedTaxes)) {
                if ($tax['rate'] > $groupedTaxes[$key]['rate']) {
                    $groupedTaxes[$key]['rate'] = $tax['rate'];
                }
                $groupedTaxes[$key]['amount'] += $tax['amount'];
            } else {
                $groupedTaxes[$key] = $tax;
            }
        }

        foreach ($groupedTaxes as $groupedTaxKey => $groupedTax) {
            $groupedTaxes[$groupedTaxKey]['amount'] = $this->moneyService->format(
                $this->moneyService->getMoney($groupedTax['amount'], $currency)
            );
        }

        $roundInfo = $this->roundWithInfo($total);
        if ($currency == 'USD') {
            $roundInfo['result'] = $total;
            $roundInfo['diff'] = 0;
        }

        $roundedTotal = $roundInfo['result'];
        $grandTotalVerbal = $numberFormatter->format($roundedTotal);
        $delivery = $this->moneyService->format($this->moneyService->getMoney($deliveryTotal, $currency));
        $grandTotal = $this->moneyService->format($this->moneyService->getMoney($roundedTotal, $currency));
        $subtotal = round($roundedTotal - $deliveryTotal, 2);
        $subtotalVerbal = $numberFormatter->format($subtotal);
        $subtotal = $this->moneyService->format($this->moneyService->getMoney($subtotal, $currency));
        $customerName = $customerAddress['first_name'] ?? '';
        $customerLastName = $customerAddress['last_name'] ?? '';

        if (is_null($package->getInvoiceNumber())) {
            $package->setInvoiceNumber($package->generateInvoiceNumber());
        }

        $invoiceSnapshot = [
            "templateName" => $this->config['docmosis']['templateName'],
            "outputName" => str_replace(['/', '|', '-'], '_', 'Boodmo_Invoice_' . $package->getInvoiceNumber() . "/C") . '.doc',
            "outputFormat" => "doc",
            "accessKey" => $this->config['docmosis']['accessKey'],
            "data" => [
                "supplier_name" => $supplierProfile->getCompanyName(),
                "supplier_shipping_address" => is_object($shippingAddresses) ? $shippingAddresses->getAddress() : "",
                "supplier_shipping_city" => is_object($shippingAddresses) ? $shippingAddresses->getCity() : "",
                "supplier_shipping_state" => is_object($shippingAddresses) ? $shippingAddresses->getState() : "",
                "supplier_shipping_pin" => is_object($shippingAddresses) ? $shippingAddresses->getPin() : "",
                "supplier_vat" => $supplierProfile->getVat(),
                "supplier_gst" => $supplierProfile->getGst(),
                "supplier_cst" => $supplierProfile->getCst(),
                "supplier_pan" => $supplierProfile->getPan(),
                "customer_name" => $customerName . ' ' . $customerLastName,
                "customer_first_name" => $customerName,
                "customer_last_name" => $customerLastName,
                "customer_address" => $customerAddress['address'] ?? '',
                "customer_city" => $customerAddress['city'] ?? '',
                "customer_state" => $customerAddress['state'] ?? '',
                "customer_pin" => $customerAddress['pin'] ?? '',
                "customer_phone_number" => $customerAddress['phone'] ?? '',
                "customer_email" => $bundle->getCustomerEmail(),
                "transaction_mode" => $this->getTransactionMode($bundle->getPaymentMethod()),
                "invoice_number" => $package->getInvoiceNumber() . "/C",
                "invoice_date" => $now,
                "order_id" => $bundle->getNumber(),
                "package_id" => $package->getFullNumber(),
                "order_date" => $bundle->getCreatedAt()->format("d/m/Y"),
                "items" => $items,
                "subtotal" => $subtotal,
                "subtotal_words" => $subtotalVerbal,
                "taxes" => array_values($groupedTaxes),
                "delivery_charges" => $delivery,
                "roundoff_sign" => $roundInfo['sign'],
                "roundoff" => $this->moneyService->format($this->moneyService->getMoney($roundInfo['diff'], $currency)),
                "grand_total" => $grandTotal,
                "grand_total_words" => $grandTotalVerbal,
                "external_invoice" => $package->getExternalInvoice(),
            ],
        ];
        $invoiceSnapshot = $this->addCustomDataToSnapshot($invoiceSnapshot, $package);
        return $invoiceSnapshot;
    }

    private function renderInvoiceFile($snapshot)
    {
        $this->tryCount++;
        $url = $this->config['docmosis']['render_url'];
        $fileName = $snapshot['outputName'];
        $request = new Request();
        $request->getHeaders()->addHeaders([
            'Content-Type' => 'application/json'
        ]);
        $request->setUri($url);
        $request->setMethod('POST');
        $request->setContent(json_encode($snapshot, JSON_HEX_AMP));

        $client = new Client();

        $client->setAdapter(new \Zend\Http\Client\Adapter\Curl());

        $response = $client->dispatch($request);

        if ($response->isOk()) {
            $this->streamInvoiceDocument($fileName, $response);
        } else {
            return $response->getContent();
        }
    }

    private function streamInvoiceDocument($fileName, $response)
    {
        header('Content-Description: File Transfer');
        header('Content-Type: application/msword');
        header('Content-Disposition: attachment; filename="'.$fileName.'";');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        echo $response->getContent();
        exit;
    }

    private function getTransactionMode($paymentMethod)
    {
        $paymentMap = [
            'razorpay'  => 'Prepaid',
            'hdfc_bank' => 'Bank',
            'cash'      => 'Cash',
            'paypal'    => 'PayPal',
            'card'      => 'Prepaid',
            'hdfc'      => 'Prepaid'
        ];

        return $paymentMap[$paymentMethod] ?? '';
    }

    public function roundWithInfo($originalNumber)
    {
        $roundedNumber = round($originalNumber);
        $roundSign = ($roundedNumber > $originalNumber) ? '+' : '-';
        $roundDiff = ($roundedNumber > $originalNumber)
            ? $roundedNumber - $originalNumber
            : ($roundedNumber === $originalNumber ? 0 : $originalNumber - $roundedNumber);

        return ['result' => $roundedNumber, 'sign' => $roundSign, 'diff' => round($roundDiff, 2)];
    }

    private function getSupplierByPackage(OrderPackage $package): Supplier
    {
        $supplier = $package->getSupplierProfile();
        if ($supplier->getAccountingType() === Supplier::ACCOUNTING_TYPE_AGENT) {
            $supplier = $supplier->getAccountingAgent();
        }
        return $supplier;
    }

    private function isBlockedInvoiceGeneration(OrderPackage $package): bool
    {
        return in_array($package->getSupplierProfile()->getId(), self::SUPPLIER_IDS_WITH_BLOCKED_INVOICES);
    }
}
