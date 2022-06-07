<?php

namespace Boodmo\Sales\Service;

use Boodmo\Email\Service\EmailManager;
use Boodmo\Sales\Entity\CreditPoint;
use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderCreditPointApplied;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Entity\Payment;
use Boodmo\Sales\Model\Payment\Provider\AbstractPaymentProvider;
use Boodmo\Sales\Repository\OrderCreditPointAppliedRepository;
use Boodmo\Sales\Repository\OrderPackageRepository;
use Boodmo\Shipping\Model\Providers\AbstractShippingProvider;
use Boodmo\Shipping\Service\ShippingService;
use Boodmo\User\Entity\UserProfile\Supplier;
use Boodmo\User\Service\AddressService;
use OpsWay\ZohoBooks\Api;
use OpsWay\ZohoBooks\Exception as ZohoException;

class FinanceService
{
    public const TYPE_DOC_INVOICE = 'C';
    public const TYPE_DOC_CUSTOMER_CREDIT = 'CR';
    public const TYPE_DOC_CREDIT_POINTS = 'CP';
    public const TYPE_DOC_SUP_BILL = 'S';
    public const TYPE_DOC_SUP_RETURN_VENDOR_CREDIT = 'SR';
    public const TYPE_DOC_SUP_VENDOR_CREDIT = 'B';
    public const TYPE_DOC_SUP_CREDIT_RETURN = 'BR';
    public const TYPE_DOC_MARKETPLACE_INVOICE = 'A';

    public const AFFILIATE_TAG = '458850000000000335';
    public const AFFILIATE_OPTIONS = [
        'web' => '458850000000900115',
        'autoportal' => '458850000000885041',
        'app_ios' => '458850000000885043',
        'app_android' => '458850000000900113',
    ];
    public const CURRENCY_TAG = '458850000000000333';
    public const CURRENCY_OPTIONS = [
        'INR' => '458850000000429355',
        'USD' => '458850000000429357',
    ];
    public const ZOHO_STATES = [
        "andaman & nicobar islands" => "AN",
        "andhra pradesh" => "AD",
        "arunachal pradesh" => "AR",
        "assam" => "AS",
        "bihar" => "BR",
        "chandigarh" => "CH",
        "chhattisgarh" => "CG",
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
        "meghalaya" => "ML",
        "mizoram" => "MZ",
        "nagaland" => "NL",
        "odisha" => "OD",
        "puducherry" => "PY",
        "punjab" => "PB",
        "rajasthan" => "RJ",
        "sikkim" => "SK",
        "tamil nadu" => "TN",
        "telangana" => "TS",
        "tripura" => "TR",
        "uttar pradesh" => "UP",
        "uttarakhand" => "UK",
        "west bengal" => "WB",
    ];
    public const TAX_INVOICE = [
        'invoices' => '458850000002574629',
        'bills' => '458850000002588959',
    ];
    /**
     * @var Api
     */
    private $zohoBooks;
    /**
     * @var Api
     */
    private $zohoBooks2;
    /**
     * @var array
     */
    private $config;
    /**
     * @var array
     */
    private $config2;
    /**
     * @var ShippingService
     */
    private $shippingService;
    /**
     * @var PaymentService
     */
    private $paymentService;
    /**
     * @var OrderPackageRepository
     */
    private $orderPackageRepository;
    /**
     * @var EmailManager
     */
    private $emailManager;
    /**
     * @var AddressService
     */
    private $addressService;
    /**
     * @var OrderCreditPointAppliedRepository
     */
    private $creditPointRepository;

    /**
     * FinanceService constructor.
     *
     * @param ShippingService $shippingService
     * @param AddressService  $addressService
     * @param PaymentService  $paymentService
     * @param OrderPackageRepository  $orderPackageRepository
     * @param EmailManager    $emailManager
     * @param Api             $zohoBooks
     * @param Api             $zohoBooks2
     * @param array           $config
     * @param array           $config2
     * @param OrderCreditPointAppliedRepository           $creditPointRepository
     */
    public function __construct(
        ShippingService $shippingService,
        AddressService $addressService,
        PaymentService $paymentService,
        OrderPackageRepository $orderPackageRepository,
        EmailManager $emailManager,
        Api $zohoBooks,
        Api $zohoBooks2,
        array $config,
        array $config2,
        OrderCreditPointAppliedRepository $creditPointRepository
    ) {
        $this->zohoBooks = $zohoBooks;
        $this->zohoBooks2 = $zohoBooks2;
        $this->addressService = $addressService;
        $this->config = $config;
        $this->config2 = $config2;
        $this->shippingService = $shippingService;
        $this->paymentService = $paymentService;
        $this->orderPackageRepository = $orderPackageRepository;
        $this->emailManager = $emailManager;
        $this->creditPointRepository = $creditPointRepository;
    }

    public function createSupplierContact(Supplier $supplierProfile, string $type = 'boodmo'): bool
    {
        $types = [
            'boodmo' => 'zohoBooks',
            'smart' => 'zohoBooks2'
        ];
        if (!array_key_exists($type, $types)) {
            return false;
        }
        $zohoBooks = $types[$type];
        if ($type == 'smart' && !$this->config2['is_enabled']) {
            return false;
        }
        $mid = $supplierProfile->getId();
        $name = $supplierProfile->getName();
        $displayName = $mid . ' ' . $name;
        $email = $supplierProfile->getContactEmail();
        $addresses = [
            'billing'  => $this->addressService->getSupplierAddress($supplierProfile, 'billing'),
            'shipping' => $this->addressService->getSupplierAddress($supplierProfile, 'shipping')
        ];

        $request = [
            'contact_type'    => 'vendor',
            'contact_name'    => $displayName,
            'company_name'    => $displayName,
            'email'           => $email,
            'gst_treatment'   => 'business_none',
            'contact_persons' => [
                [
                    'phone' => $supplierProfile->getPhone(),
                    'email' => $email
                ]
            ],
            'billing_address' => [
                'address' => $addresses['billing']->getAddress(),
                'city'    => $addresses['billing']->getCity(),
                'state'   => $addresses['billing']->getState(),
                'zip'     => $addresses['billing']->getPin(),
            ],
            'shipping_address' => [
                'address' => $addresses['shipping']->getAddress(),
                'city'    => $addresses['shipping']->getCity(),
                'state'   => $addresses['shipping']->getState(),
                'zip'     => $addresses['shipping']->getPin(),
            ]
        ];

        if ($supplierProfile->getLocality() === Supplier::LOCALITY_LOCAL) {
            $request['place_of_contact'] = self::ZOHO_STATES[strtolower($addresses['billing']->getState())];
        }

        $contact = $this->$zohoBooks->contacts()->create($request);
        if ($type == 'boodmo') {
            $supplierProfile->setZohoBooksId($contact['contact_id']);
        } elseif ($type == 'smart') {
            $supplierProfile->setZohoSmart($contact['contact_id']);
        }
        return true;
    }

    /**
     * Это момент когда поставщик отправил логистам товар
     *
     * @param OrderPackage $package
     * @param \DateTime    $customDate
     */
    public function shippingDispatchedObserver(OrderPackage $package, \DateTime $customDate = null)
    {
        $currency = $package->getCurrency();
        $supplierProfile = $package->getSupplierProfile();
        $countPrepaidBills = $package->getBundle()->getBills()->filter(function (OrderBill $bill) use ($currency) {
            return $bill->getCurrency() === $currency
                && $bill->getType() === $bill::TYPE_PREPAID;
        })->count();
        $periodDays = ($countPrepaidBills === 0)
            ? $supplierProfile->getCodPayment()
            : $supplierProfile->getCardPayment();
        $now = new \DateTimeImmutable();
        if ($customDate === null) {
            $dueDate = new \DateTimeImmutable();
        } else {
            $dueDate = clone $customDate;
            $now = clone $customDate;
        }

        if ($periodDays) {
            $dueDate = $dueDate->add(new \DateInterval('P' . ($periodDays) . 'D'));
        }
        $tags = $this->getZohoTags($package);

        $tax_id = null;
        if ($supplierProfile->getLocality() === Supplier::LOCALITY_LOCAL) {
            $zohoContact = $this->zohoBooks->contacts()->get($package->getSupplierProfile()->getZohoBooksId());
            $supplierState = $zohoContact['place_of_contact'];
            $destinationState = self::ZOHO_STATES[strtolower($package->getDestinationState())];
            if ($supplierState == $destinationState) {
                $tax_id = $this->config['standard_rate_tax_id'];
            } else {
                $tax_id = $this->config['igst_tax_id'];
            }
        } else {
            $tax_id = $this->config['zero_tax_id'];
        }

        //-------------------- ZohoBooks boodmo.com --------------------
        // Создаем для будмо обязательство оплатить поставщику текущий пакет
        try {
            $request = [
                'vendor_id' => $supplierProfile->getZohoBooksId(),
                'bill_number' => $this->financeNumber($package, self::TYPE_DOC_SUP_BILL),
                'reference_number' => $package->getId(),
                'date' => $now->format('Y-m-d'),
                'due_date' => $dueDate->format('Y-m-d'),
                'is_inclusive_tax' => false,
                'gst_treatment' => 'business_none',
                'custom_fields' => [
                    [
                        'label' => 'TaxInvoice#',
                        'value' => $package->getExternalInvoice(),
                        'index' => 1,
                        'custom_field_id' => $this->getZohoCustomFieldTaxInvoiceId('bills'),
                    ]
                ],
                'line_items' => [
                    [
                        'account_id' => $this->config['acc_cost_sold'],
                        'name' => 'Products',
                        'description' => 'Package #' . $package->getFullNumber(),
                        'rate' => $package->getGrandTotal()/100,
                        'quantity' => 1,
                        'reverse_charge_tax_id' => $this->config['zero_tax_id'],
                        'tags' => $tags,
                    ],
                ],
            ];
            $this->zohoBooks->bills()->create($request);
        } catch (ZohoException $e) {
            $this->sendEmailToFinanceManager($this->createErrorMessage($this->config['organization'], __METHOD__ . ": create bill", $package, $request, $e));
        }

        try {
            $invoiceDueDate = clone $now;
            // Выставляем счет (invoice) для покупателя на текущий пакет
            $request = [
                'customer_id' => $this->config[strtolower($package->getCurrency()) . '_retail_customer_id'],
                'date' => $now->format('Y-m-d'),
                'due_date' => $invoiceDueDate->add(new \DateInterval('P' . $package->getDeliveryDays() . 'D'))->format('Y-m-d'),
                'invoice_number' => $this->financeNumber($package, self::TYPE_DOC_INVOICE),
                'reference_number' => $package->getId(),
                'salesperson_name' => $package->getBundle()->getCustomerProfile()->getFullName(),
                'custom_fields' => [
                    [
                        'label' => 'TaxInvoice#',
                        'value' => $package->getExternalInvoice(),
                        'index' => 1,
                        'custom_field_id' => self::TAX_INVOICE['invoices'],
                    ]
                ],
                'line_items' => [
                    [
                        'name' => 'Package #' . $package->getFullNumber(),
                        'rate' => $package->getGrandTotal()/100,
                        'tags' => $tags,
                    ]
                ]
            ];
            $invoice = $this->zohoBooks->invoices()->create($request);
            $this->zohoBooks->invoices()->markAsSent($invoice['invoice_id']);
        } catch (ZohoException $e) {
            $this->sendEmailToFinanceManager($this->createErrorMessage($this->config['organization'], __METHOD__ . ": create invoice", $package, $request, $e));
        }

        try {
            $items = [];
            $item = [
                'account_id' => $this->config['acc_income_shipping'],
                'name' => 'Shipping Charges',
                'rate' => $package->getDeliveryTotal()/100,
                'quantity' => 1,
                'reverse_charge_tax_id' => $tax_id,
                'tags' => $tags,
            ];
            $items[] = $item;
            if ($package->getFacilitationFee(true) > 0) {
                $item = [
                    'account_id' => $this->config['acc_market_fee'],
                    'name' => 'Marketplace Fee',
                    'rate' => $package->getFacilitationFee(true)/100,
                    'quantity' => 1,
                    'reverse_charge_tax_id' => $tax_id,
                    'tags' => $tags,
                ];
                $items[] = $item;
            }

            //1) ----- Создать требование к поставщику заплатить комисию + вернуть нам деньги за доставку
            $request = [
                'vendor_id' => $package->getSupplierProfile()->getZohoBooksId(),
                'vendor_credit_number' => $this->financeNumber($package, self::TYPE_DOC_SUP_VENDOR_CREDIT),
                'reference_number' => $package->getId(),
                'date' => $now->format('Y-m-d'),
                'is_inclusive_tax' => false,
                'gst_treatment' => 'business_gst',
                'line_items' => $items,
            ];

            if ($supplierProfile->getLocality() === Supplier::LOCALITY_LOCAL) {
                $request['source_of_supply'] = $supplierState;
                $request['destination_of_supply'] = $destinationState;
            }

            $this->zohoBooks->vendorCredits()->create($request);
        } catch (ZohoException $e) {
            $this->sendEmailToFinanceManager($this->createErrorMessage($this->config['organization'], __METHOD__ . ": create vendor credit", $package, $request, $e));
        }

        //-------------------- ZohoBooks Smart Parts Online ltd --------------------
        if (!$this->config2['is_enabled'] || !$package->getSupplierProfile()->getZohoSmart() || ($package->getCurrency() == 'USD')) {
            return;
        }
        try {
            // Create Bill document for Supplier
            $request = [
                'vendor_id' => $supplierProfile->getZohoSmart(),
//                'bill_number' => $this->financeNumber($package, self::TYPE_DOC_SUP_BILL),
                'reference_number' => $package->getId(),
                'date' => $now->format('Y-m-d'),
                'due_date' => $dueDate->format('Y-m-d'),
                'is_inclusive_tax' => false,
                'notes' => 'Shifting of Liability from WIC to Vendor account via control account',
                'line_items' => [
                    [
                        'account_id' => $this->config2['control_expense_account'],
                        'name' => 'Spare parts',
                        'description' => 'Package #' . $package->getFullNumber(),
                        'rate' => $package->getCostTotal()/100,
                        'quantity' => 1,
                        'tags' => $tags,
                    ],
                ],
            ];
            $bill2 = $this->zohoBooks2->bills()->create($request);
        } catch (ZohoException $e) {
            $this->sendEmailToFinanceManager($this->createErrorMessage($this->config2['organization'], __METHOD__ . ": bill create", $package, $request, $e));
        }

        try {
            // - Comment - link to downloadable invoice
            if (isset($bill2['bill_id'])) {
                $package->setFacilitationInvoiceNumber($bill2['bill_number']);
                $this->orderPackageRepository->save($package);
                $request = [
                    'description' => 'http://api.' . getenv('DOMAIN') . '/api/rpc/invoice/get_document_facilitation?id=' . $package->getId()
                ];
                $this->zohoBooks2->bills()->addComment($bill2['bill_id'], $request);
            }
        } catch (ZohoException $e) {
            $this->sendEmailToFinanceManager($this->createErrorMessage($this->config2['organization'], __METHOD__ . ": bill add comment", $package, $request, $e));
        }

        try {
            // Create manual journal #1 entry
            $request = [
                'journal_date' => $now->format('Y-m-d'),
                'currency_id' => $this->config2['currency_id'],
                'reference_number' => $package->getFullNumber(),
                'notes' => 'Shifting of Liability from WIC to Vendor account via control account',
                'line_items' => [
                    [
                        'account_id' => $this->config2['walk_in_customer_prepaid'],
                        'debit_or_credit' => 'debit',
                        'amount' => $package->getGrandTotal()/100,
                    ],
                    [
                        'account_id' => $this->config2['control_expense_account'],
                        'debit_or_credit' => 'credit',
                        'amount' => $package->getGrandTotal()/100,
                    ],
                ],
            ];
            $this->zohoBooks2->journals()->create($request);
        } catch (ZohoException $e) {
            $this->sendEmailToFinanceManager($this->createErrorMessage($this->config2['organization'], __METHOD__ . ": journal create", $package, $request, $e));
        }

        try {
            // Create manual journal #2 entry
            $request = [
                'journal_date' => $now->format('Y-m-d'),
                'currency_id' => $this->config2['currency_id'],
                'reference_number' => $package->getFullNumber(),
                'notes' => 'Marketplace fee and shipping charges charged from Vendor',
                'line_items' => [
                    [
                        'account_id' => $this->config2['control_expense_account'],
                        'debit_or_credit' => 'credit',
                        'amount' => $package->getFacilitationFee() + $package->getDeliveryTotal()/100,
                    ],
                    [
                        'account_id' => $this->config2['income_facilitation_fees'],
                        'debit_or_credit' => 'debit',
                        'amount' => $package->getFacilitationFee() / 1.15,
                        'customer_id' => $supplierProfile->getZohoSmart(),
                        'tax_id' => $this->config2['service_tax_id'],
                    ],
                    [
                        'account_id' => $this->config2['income_shipping_charges'],
                        'debit_or_credit' => 'debit',
                        'amount' => $package->getDeliveryTotal()/100 / 1.15,
                        'customer_id' => $supplierProfile->getZohoSmart(),
                        'tax_id' => $this->config2['service_tax_id'],
                    ],
                ],
            ];
            $this->zohoBooks2->journals()->create($request);
        } catch (ZohoException $e) {
            $this->sendEmailToFinanceManager($this->createErrorMessage($this->config2['organization'], __METHOD__ . ": journal create", $package, $request, $e));
        }
    }

    public function shippingDeliveredObserver(OrderPackage $package, \DateTime $customDate = null)
    {
        $now = new \DateTimeImmutable();
        if (!is_null($customDate)) {
            $now = $customDate;
        }

        //2) Сделать взаиморасчет между тем, что надо заплатить поставщику и тем, что он нам должен вернуть
        $credits = $this->zohoBooks->vendorCredits()->getList([
            'vendor_credit_number' => $this->financeNumber($package, self::TYPE_DOC_SUP_VENDOR_CREDIT)
        ]);
        if (empty($credits[0])) {
            $request = [];
            $e = new ZohoException("Vendor credit " . $this->financeNumber($package, self::TYPE_DOC_SUP_VENDOR_CREDIT) . " not found in Zoho Books");
//            $this->sendEmailToFinanceManager($this->createErrorMessage($this->config['organization'], __METHOD__ . ": get vendor credit", $package, $request, $e));
        }
        $bills = $this->zohoBooks->bills()->getList([
            'bill_number' => $this->financeNumber($package, self::TYPE_DOC_SUP_BILL)
        ]);
        if (empty($bills[0])) {
            $request = [];
            $e = new ZohoException("Bill " . $this->financeNumber($package, self::TYPE_DOC_SUP_BILL) . " not found in Zoho Books");
//            $this->sendEmailToFinanceManager($this->createErrorMessage($this->config['organization'], __METHOD__ . ": get bill", $package, $request, $e));
        }
        if (!empty($credits[0]) && !empty($bills[0])) {
            try {
                $request = [
                    'bills' => [
                        [
                            'bill_id'        => $bills[0]['bill_id'] ?? null,
                            'amount_applied' => $credits[0]['total'],
                        ]
                    ]
                ];
                $this->zohoBooks->vendorCredits()->applyToBill($credits[0]['vendor_credit_id'], $request);
            } catch (ZohoException $e) {
                $this->sendEmailToFinanceManager($this->createErrorMessage($this->config['organization'], __METHOD__ . ": vendor credits apply to bill", $package, $request, $e));
            }
        }
        unset($bills);
        // ---------------------------------------------------
        // 3) Погасить счет (Sales Invoice) который мы выставили пользователю ранее из его оплат(ы) --------
        $usedPayments = [];
        $usedCreditPoints = [];
        $currency = $package->getCurrency();
        //Вытянуть все boodmo payments & credit points которые относятся к этому пакету (всему бандлу) и сумму привязки
        $package->getBundle()->getPaidBills()->filter(function (OrderBill $bill) use ($currency) {
            return $bill->getCurrency() === $currency && $bill->getTotal() / 100 > 0;
        })->map(function (OrderBill $bill) use (&$usedPayments, &$usedCreditPoints) {
            foreach ($bill->getPaymentsApplied() as $applied) {
                $id = $applied->getPayment()->getZohoBooksId();
                $usedPayments[$id] = ($usedPayments[$id] ?? 0) + $applied->getAmount() / 100;
            }
            foreach ($bill->getCreditPointsApplied() as $applied) {
                $id = $applied->getCreditPoint()->getZohoBooksId();
                $usedCreditPoints[$id] = ($usedCreditPoints[$id] ?? 0) + $applied->getAmount() / 100;
            }
        });
        // Достать из зохо инвойс, который мы будем погашать зохо оплатами
        $invoices = $this->zohoBooks->invoices()->getList([
            'invoice_number' => $this->financeNumber($package, self::TYPE_DOC_INVOICE)
        ]);
        // Если инвойса нет - дальше не продолжаем
        $invoiceId = $invoices[0]['invoice_id'] ?? null;
        if ($invoiceId === null) {
            return;
        }
        $totalInvoiceAmount = $package->getGrandTotal() / 100; // запоминаем сумму которую надо погасить
        try {
            $free = 0;
            foreach ($usedPayments as $zid => $amount) {
                if ($totalInvoiceAmount <= 0) {
                    break;
                }
                $zohoPay = $this->zohoBooks->customerPayments()->get($zid);
                if ($zohoPay['unused_amount'] > 0) {
                    $apply = min($zohoPay['unused_amount'], $amount, $totalInvoiceAmount);
                    $zohoInvoiceBalance = $invoices[0]['balance'] ?? null;
                    if (!empty($zohoInvoiceBalance) && $zohoInvoiceBalance < $apply) {
                        $apply = $zohoInvoiceBalance;
                    }
                    $request = [
                        'payment_id'     => $zid,
                        'amount_applied' => $apply
                    ];
                    $this->zohoBooks->invoices()->applyCredits($invoiceId, [
                        'invoice_payments' => [
                            $request
                        ]
                    ]);
                    //$free = $usedPayments[$zid] - $apply + $free;
                    $totalInvoiceAmount -= $apply;
                }
            }
        } catch (ZohoException $e) {
            $this->sendEmailToFinanceManager(
                $this->createErrorMessage(
                    $this->config['organization'],
                    __METHOD__ . ": invoices apply credits payments",
                    $package,
                    $request,
                    $e
                )
            );
        }
        try {
            $free = 0;
            foreach ($usedCreditPoints as $zid => $amount) {
                if ($totalInvoiceAmount <= 0) {
                    break;
                }
                $zohoCreditNote = $this->zohoBooks->creditNotes()->get($zid);
                if ($zohoCreditNote['unused_amount'] > 0) {
                    $apply = min($zohoCreditNote['unused_amount'], $amount, $totalInvoiceAmount);
                    $zohoInvoiceBalance = $invoices[0]['balance'] ?? null;
                    if (!empty($zohoInvoiceBalance) && $zohoInvoiceBalance < $apply) {
                        $apply = $zohoInvoiceBalance;
                    }
                    $request = [
                        'creditnote_id'  => $zid,
                        'amount_applied' => $apply
                    ];
                    $this->zohoBooks->invoices()->applyCredits($invoiceId, [
                        'apply_creditnotes' => [
                            $request
                        ]
                    ]);
                    //$free = $usedCreditPoints[$zid] - $apply + $free;
                    $totalInvoiceAmount -= $apply;
                }
            }
        } catch (ZohoException $e) {
            $this->sendEmailToFinanceManager(
                $this->createErrorMessage(
                    $this->config['organization'],
                    __METHOD__ . ": invoices apply credits notes",
                    $package,
                    $request,
                    $e
                )
            );
        }
    }

    public function updateCustomerPaymentReference(Payment $payment): void
    {
        $bundles = array_unique($payment->getBills()->map(function (OrderBill $bill) {
            return $bill->getBundle()->getId();
        })->toArray());
        $reference = 'C:'.$payment->getCustomerProfile()->getId();
        if ($bundles) {
            $reference .= ' Order:'.implode(',', $bundles);
        }
        try {
            $data = $this->zohoBooks->customerPayments()->get($payment->getZohoBooksId());
            $data['reference_number'] = $reference;
            $this->zohoBooks->customerPayments()->update($data);
        } catch (\Exception $e) {
        }
    }

    /**
     * @todo Refactor this
     *
     * @param Payment     $payment
     * @param string|null $shippingMethod
     */
    public function createCustomerPayment(Payment $payment, string $shippingMethod = null): void
    {
        if ($zohoPaymentAccountId = $this->getZohoPaymentAccount($payment, $shippingMethod)) {
            //$referenceNumber = $payment->getBundle()->getId()
            $zohoPay = $this->zohoBooks->customerPayments()->create([
                'customer_id'       => $this->config[strtolower($payment->getCurrency()) . '_retail_customer_id'],
                'date'              => $payment->getUpdatedAt()->format('Y-m-d'),
                'amount'            => $payment->getTotal()/100,
                'payment_mode'      => $payment->getPaymentMethod(),
                'account_id'        => $zohoPaymentAccountId,
                'reference_number'  => 'C:'.$payment->getCustomerProfile()->getId(),
            ]);
            $payment->setZohoBooksId($zohoPay['payment_id']);
        } else {
            throw new \RuntimeException('Could not prepare ZohoBooks payment account id', 422);
        }
    }

    private function getZohoPaymentAccount(Payment $payment, string $shippingMethod = null): ?string
    {
        /* @var AbstractPaymentProvider $paymentProvider */
        /* @var AbstractShippingProvider $shippingProvider */

        $result = null;

        try {
            $paymentProvider = $this->paymentService->getProviderByCode($payment->getPaymentMethod());

            if ($paymentProvider->isPrepaid()) {
                // like razorpay or paypal
                $result = $paymentProvider->setConfig($this->config)->getZohoPaymentAccount();
            } else {
                //like cash
                if ($shippingMethod === null
                    or !($shippingProvider = $this->shippingService->getProviderByCode($shippingMethod))
                ) {
                    throw new \RuntimeException('For cash payment need use method of shipping box.', 422);
                }

                $result = $shippingProvider->setConfig($this->config)->getZohoPaymentAccount();
            }
        } catch (\Exception $exception) {
            throw new \RuntimeException($exception->getMessage(), 422);
        }

        return $result;
    }

    public function shippingRejectedObserver(OrderPackage $package)
    {
        if (strpos($package->getSupplierProfile()->getCompanyName(), 'AAA') === false) {
            $now = new \DateTimeImmutable();
            $tags = $this->getZohoTags($package);
            try {
                // Create Sales->Credit Notes and apply it to Customer Invoice
                $invoices = $this->zohoBooks->invoices()->getList([
                    'invoice_number' => $this->financeNumber($package, self::TYPE_DOC_INVOICE)
                ]);
                $invoiceId = $invoices[0]['invoice_id'] ?? null;
                $request = [
                    'customer_id' => $this->config[strtolower($package->getCurrency()) . '_retail_customer_id'],
                    'date' => $now->format('Y-m-d'),
                    'creditnote_number' => $this->financeNumber($package, self::TYPE_DOC_CUSTOMER_CREDIT),
                    'reference_number' => $package->getId(),
                    'line_items' => [
                        [
                            'rate' => $invoices[0]['balance'] ?? 0,
                            'account_id'  => $this->config['gross_sales'],
                            'name'        => 'Return Products',
                            'description' => 'Package #' . $package->getFullNumber(),
                            'tags' => $tags,
                        ]
                    ]
                ];
                $this->zohoBooks->creditNotes()->create(
                    $request,
                    ['invoice_id' => $invoiceId]
                );
            } catch (ZohoException $e) {
                $this->sendEmailToFinanceManager($this->createErrorMessage($this->config['organization'], __METHOD__ . ": credit notes create", $package, $request, $e));
            }
        }
    }

    public function onCreditPointCreate(CreditPoint $creditPoint, OrderBundle $orderBundle)
    {
        $now = new \DateTimeImmutable();
        $emulatePackage = new OrderPackage();
        $emulatePackage->setCurrency($creditPoint->getCurrency());
        $tags = $this->getZohoTags($emulatePackage);
        $action = '';
        $request = [];

        try {
            $request = [
                'customer_id' => $this->config[strtolower($creditPoint->getCurrency()) . '_retail_customer_id'],
                'date' => $now->format('Y-m-d'),
                'reference_number' => 'C:'.$creditPoint->getCustomerProfile()->getId().' Order:'.$orderBundle->getId(),
                'line_items' => [
                    [
                        'name' => $creditPoint->getType(),
                        'account_id' => $this->getAccountIdByCreditPoint($creditPoint),
                        'description' => 'Customer Credit Points for ' . $creditPoint->getCustomerProfile()->getId(),
                        'tags' => $tags,
                        'rate' => 0
                    ]
                ]
            ];

            $number = substr(md5($creditPoint->getId()), 0, 16);
            if ($creditPoint->getTotal() > 0) {
                $action = 'credit notes create';
                $request['creditnote_number'] = $number;
                $request['line_items'][0]['rate'] = $creditPoint->getTotal()/100;

                $zohoPay = $this->zohoBooks->creditNotes()->create($request);
                $creditPoint->setZohoBooksId($zohoPay['creditnote_id']);
            } elseif ($creditPoint->getTotal() < 0) {
                $action = 'invoice create';
                $request['invoice_number'] = $number;
                $request['due_date'] = $now->add(new \DateInterval('P' . 7 . 'D'))->format('Y-m-d');
                $request['salesperson_name'] = $creditPoint->getCustomerProfile()->getFullName();
                $request['line_items'][0]['rate'] = abs($creditPoint->getTotal()/100);

                $zohoPay = $this->zohoBooks->invoices()->create($request);
                $creditPoint->setZohoBooksId($zohoPay['invoice_id']);
            }
        } catch (ZohoException $e) {
            $this->sendEmailToFinanceManager(
                $this->createErrorMessage(
                    $this->config['organization'],
                    __METHOD__ . ': ' . $action,
                    $emulatePackage,
                    $request,
                    $e
                )
            );
        }
    }

    public function updateCreditNoteReference(CreditPoint $creditPoint): void
    {
        $bundles = array_unique(
            $creditPoint->getCreditPointsApplied()->map(function (OrderCreditPointApplied $applied) {
                return $applied->getBill()->getBundle()->getId();
            })->toArray()
        );
        $reference = 'C:'.$creditPoint->getCustomerProfile()->getId();
        if ($bundles) {
            $reference .= 'Order:'.implode(',', $bundles);
        }
        try {
            if ($creditPoint->getTotal() > 0) {
                $data = $this->zohoBooks->creditNotes()->get($creditPoint->getZohoBooksId());
                $data['reference_number'] = $reference;
                $this->zohoBooks->creditNotes()->update($data);
            } else {
                $data = $this->zohoBooks->invoices()->get($creditPoint->getZohoBooksId());
                $data['reference_number'] = $reference;
                $this->zohoBooks->invoices()->update($data);
            }
        } catch (\Exception $e) {
        }
    }

    public function shippingDeniedObserver(OrderPackage $package)
    {
        //-------------------- ZohoBooks boodmo.com --------------------
        $tags = $this->getZohoTags($package);
        $now = new \DateTimeImmutable();
        try {
            $credits = $this->zohoBooks->vendorCredits()->getList([
                'vendor_credit_number' => $this->financeNumber($package, self::TYPE_DOC_SUP_VENDOR_CREDIT)
            ]);
            $bills = $this->zohoBooks->bills()->getList([
                'bill_number' => $this->financeNumber($package, self::TYPE_DOC_SUP_BILL)
            ]);
            $request = [
                'bills' => [
                    [
                        'bill_id'        => $bills[0]['bill_id'] ?? null,
                        'amount_applied' => $credits[0]['balance'] ?? 0,
                    ]
                ]
            ];
            $this->zohoBooks->vendorCredits()->applyToBill($credits[0]['vendor_credit_id'] ?? 0, $request);
        } catch (ZohoException $e) {
            $this->sendEmailToFinanceManager($this->createErrorMessage($this->config['organization'], __METHOD__ . ": vendor credits apply to bill", $package, $request, $e));
        }

        //-------------------- ZohoBooks Smart Parts Online ltd --------------------
        if (!$this->config2['is_enabled'] || !$package->getSupplierProfile()->getZohoSmart() || ($package->getCurrency() == 'USD')) {
            return;
        }
        $supplierProfile = $package->getSupplierProfile();
        try {
            // same as in “Shipping: Rejected => to Returned to Supplier”
            // Create Vendor Credit document for Supplier
            $request = [
                'vendor_id'        => $supplierProfile->getZohoSmart(),
                'reference_number' => $package->getId(),
                'date' => $now->format('Y-m-d'),
                'is_inclusive_tax' => false,
                'notes' => 'Shifting of Liability from WIC to Vendor account via control account',
                'line_items'       => [
                    [
                        'account_id' => $this->config2['control_expense_account'],
                        'name'       => 'Spare parts',
                        'description' => 'Package #'.$package->getFullNumber(),
                        'rate'       => $package->getCostTotal()/100,
                        'quantity'   => 1,
                        'tags' => $tags,
                    ],
                ],
            ];
            $this->zohoBooks2->vendorCredits()->create($request);
        } catch (ZohoException $e) {
            $this->sendEmailToFinanceManager($this->createErrorMessage($this->config2['organization'], __METHOD__ . ": vendor credit create", $package, $request, $e));
        }

        try {
            // Create manual journal #1 entry
            $request = [
                'journal_date' => $now->format('Y-m-d'),
                'currency_id' => $this->config2['currency_id'],
                'reference_number' => $package->getFullNumber(),
                'notes' => 'Returned to Supplier',
                'line_items'       => [
                    [
                        'account_id' => $this->config2['walk_in_customer_prepaid'],
                        'debit_or_credit' => 'credit',
                        'amount' => $package->getGrandTotal()/100,
                    ],
                    [
                        'account_id' => $this->config2['control_expense_account'],
                        'debit_or_credit' => 'debit',
                        'amount' => $package->getGrandTotal()/100,
                    ],
                ],
            ];
            $this->zohoBooks2->journals()->create($request);
        } catch (ZohoException $e) {
            $this->sendEmailToFinanceManager($this->createErrorMessage($this->config2['organization'], __METHOD__ . ": journal create", $package, $request, $e));
        }

        try {
            // Create manual journal #2 entry
            $request = [
                'journal_date' => $now->format('Y-m-d'),
                'currency_id' => $this->config2['currency_id'],
                'reference_number' => $package->getFullNumber(),
                'notes' => 'Returned to Supplier',
                'line_items'       => [
                    [
                        'account_id' => $this->config2['control_expense_account'],
                        'debit_or_credit' => 'debit',
                        'amount' => $package->getFacilitationFee() + $package->getDeliveryTotal()/100,
                    ],
                    [
                        'account_id' => $this->config2['income_facilitation_fees'],
                        'debit_or_credit' => 'credit',
                        'amount' => $package->getFacilitationFee() / 1.15,
                        'customer_id' => $supplierProfile->getZohoSmart(),
                        'tax_id' => $this->config2['service_tax_id'],
                    ],
                    [
                        'account_id' => $this->config2['income_shipping_charges'],
                        'debit_or_credit' => 'credit',
                        'amount' => $package->getDeliveryTotal()/100 / 1.15,
                        'customer_id' => $supplierProfile->getZohoSmart(),
                        'tax_id' => $this->config2['service_tax_id'],
                    ],
                ],
            ];
            $this->zohoBooks2->journals()->create($request);
        } catch (ZohoException $e) {
            $this->sendEmailToFinanceManager($this->createErrorMessage($this->config2['organization'], __METHOD__ . ": journal create", $package, $request, $e));
        }

        try {
            // same as in “Shipping:Request Sent =>  Dispatched” with the following changes:
            // manual journal entry #1 Line1:  Account: AAA Sale
            // Create Bill document for Supplier
            $request = [
                'vendor_id'        => $supplierProfile->getZohoSmart(),
                'bill_number'      => $this->financeNumber($package, self::TYPE_DOC_SUP_BILL),
                'reference_number' => $package->getId(),
                'date' => $now->format('Y-m-d'),
                'due_date'         => $now->add(new \DateInterval('P1D'))->format('Y-m-d'),
                'is_inclusive_tax' => false,
                'notes' => 'Shifting of Liability from WIC to Vendor account via control account',
                'line_items'       => [
                    [
                        'account_id' => $this->config2['control_expense_account'],
                        'name'       => 'Spare parts',
                        'description' => 'Package #'.$package->getFullNumber(),
                        'rate'       => $package->getCostTotal()/100,
                        'quantity'   => 1,
                        'tags' => $tags,
                    ],
                ],
            ];
            $bill2 = $this->zohoBooks2->bills()->create($request);
        } catch (ZohoException $e) {
            $this->sendEmailToFinanceManager($this->createErrorMessage($this->config2['organization'], __METHOD__ . ": bill create", $package, $request, $e));
        }

        try {
            // - Comment - link to downloadable invoice
            if (isset($bill2['bill_id'])) {
                $request = [
                    'description' => 'http://api.' . getenv('DOMAIN') . '/api/rpc/invoice/get_document_facilitation?id=' . $package->getId()
                ];
                $this->zohoBooks2->bills()->addComment($bill2['bill_id'], $request);
            }
        } catch (ZohoException $e) {
            $this->sendEmailToFinanceManager($this->createErrorMessage($this->config2['organization'], __METHOD__ . ": bill add comment", $package, $request, $e));
        }

        try {
            // Create manual journal #1 entry
            $request = [
                'journal_date' => $now->format('Y-m-d'),
                'currency_id' => $this->config2['currency_id'],
                'reference_number' => $package->getFullNumber(),
                'notes' => 'Shifting of Liability from WIC to Vendor account via control account',
                'line_items'       => [
                    [
                        'account_id' => $this->config2['aaa_sales'],
                        'debit_or_credit' => 'debit',
                        'amount' => $package->getGrandTotal()/100,
                    ],
                    [
                        'account_id' => $this->config2['control_expense_account'],
                        'debit_or_credit' => 'credit',
                        'amount' => $package->getGrandTotal()/100,
                    ],
                ],
            ];
            $this->zohoBooks2->journals()->create($request);
        } catch (ZohoException $e) {
            $this->sendEmailToFinanceManager($this->createErrorMessage($this->config2['organization'], __METHOD__ . ": journal create", $package, $request, $e));
        }

        try {
            // Create manual journal #2 entry
            $request = [
                'journal_date' => $now->format('Y-m-d'),
                'currency_id' => $this->config2['currency_id'],
                'reference_number' => $package->getFullNumber(),
                'notes' => 'Marketplace fee and shipping charges charged from Vendor',
                'line_items'       => [
                    [
                        'account_id' => $this->config2['control_expense_account'],
                        'debit_or_credit' => 'credit',
                        'amount' => $package->getFacilitationFee() + $package->getDeliveryTotal()/100,
                    ],
                    [
                        'account_id' => $this->config2['income_facilitation_fees'],
                        'debit_or_credit' => 'debit',
                        'amount' => $package->getFacilitationFee() / 1.15,
                        'customer_id' => $supplierProfile->getZohoSmart(),
                        'tax_id' => $this->config2['service_tax_id'],
                    ],
                    [
                        'account_id' => $this->config2['income_shipping_charges'],
                        'debit_or_credit' => 'debit',
                        'amount' => $package->getDeliveryTotal()/100 / 1.15,
                        'customer_id' => $supplierProfile->getZohoSmart(),
                        'tax_id' => $this->config2['service_tax_id'],
                    ],
                ],
            ];
            $this->zohoBooks2->journals()->create($request);
        } catch (ZohoException $e) {
            $this->sendEmailToFinanceManager($this->createErrorMessage($this->config2['organization'], __METHOD__ . ": journal create", $package, $request, $e));
        }
    }

    public function shippingReturnedToSupplierObserver(OrderPackage $package)
    {
        $now = new \DateTimeImmutable();
        $tags = $this->getZohoTags($package);
        //-------------------- ZohoBooks boodmo.com --------------------
        try {
            $bills = $this->zohoBooks->bills()->getList([
                'bill_number' => $this->financeNumber($package, self::TYPE_DOC_SUP_BILL)
            ]);
            if (!empty($bills[0]) && !empty($bills[0]['balance'])) {
                $request = [
                    'vendor_id'            => $package->getSupplierProfile()->getZohoBooksId(),
                    'vendor_credit_number' => $this->financeNumber($package, self::TYPE_DOC_SUP_RETURN_VENDOR_CREDIT),
                    'reference_number'     => $package->getId(),
                    'date'                 => $now->format('Y-m-d'),
                    'is_inclusive_tax'     => false,
                    'line_items'           => [
                        [
                            'account_id'  => $this->config['acc_cost_sold'],
                            'name'        => 'Return Products',
                            'description' => 'Package #' . $package->getFullNumber(),
                            'rate'        => $bills[0]['balance'],
                            'quantity'    => 1,
                            'reverse_charge_tax_id' => $this->config['zero_tax_id'],
                            'tags' => $tags,
                        ]
                    ],
                ];
                $credit = $this->zohoBooks->vendorCredits()->create(
                    $request,
                    [
                        'bill_id' => $bills[0]['bill_id'] ?? null,
                    ]
                );
            }
        } catch (ZohoException $e) {
            $this->sendEmailToFinanceManager($this->createErrorMessage($this->config['organization'], __METHOD__ . ": vendor credit create", $package, $request, $e));
        }

        $supplierProfile = $package->getSupplierProfile();
        try {
            $credits = $this->zohoBooks->vendorCredits()->getList([
                'vendor_credit_number' => $this->financeNumber($package, self::TYPE_DOC_SUP_VENDOR_CREDIT)
            ]);

            $tax_id = null;
            if ($supplierProfile->getLocality() === Supplier::LOCALITY_LOCAL) {
                $zohoContact = $this->zohoBooks->contacts()->get($package->getSupplierProfile()->getZohoBooksId());
                $supplierState = $zohoContact['place_of_contact'];
                $destinationState = self::ZOHO_STATES[strtolower($this->config['gst_state'])];
                if ($supplierState == $destinationState) {
                    $tax_id = $this->config['standard_rate_tax_id'];
                } else {
                    $tax_id = $this->config['igst_tax_id'];
                }

                $request['source_of_supply'] = $supplierState;
                $request['destination_of_supply'] = $destinationState;
            }

            $credit = $this->getZohoBooks()->vendorCredits()->get($credits[0]['vendor_credit_id'] ?? '0');
            $items = [];
            foreach ($credit['line_items'] as $row) {
                $item = [
                    'account_id' => $row['account_id'],
                    'name'       => $row['name'],
                    'rate'       => $row['rate'],
                    'quantity'   => 1,
//                    'tax_id'     => $row['tax_id'],
                    'reverse_charge_tax_id'     => $tax_id ?? $row['reverse_charge_tax_id'],
                    'tags' => $tags,
                ];
                $items[] = $item;
            }

            $request = [
                'bill_number'      => $this->financeNumber($package, self::TYPE_DOC_SUP_CREDIT_RETURN),
                'vendor_id'        => $supplierProfile->getZohoBooksId(),
                'reference_number' => $package->getId(),
                'date'             => $now->format('Y-m-d'),
                'due_date'         => $now->format('Y-m-d'),
                'is_inclusive_tax' => false,
                'gst_treatment'    => 'business_none',
                'line_items'       => $items,
                'custom_fields' => [
                    [
                        'label' => 'TaxInvoice#',
                        'value' => $package->getExternalInvoice(),
                        'index' => 1,
                        'custom_field_id' => self::TAX_INVOICE['bills'],
                    ]
                ],
            ];

            $bill = $this->zohoBooks->bills()->create($request);
        } catch (ZohoException $e) {
            $this->sendEmailToFinanceManager($this->createErrorMessage($this->config['organization'], __METHOD__ . ": bill create", $package, $request, $e));
        }
        try {
            $request = [
                'bills' => [
                    [
                        'bill_id'        => $bill['bill_id'] ?? null,
                        'amount_applied' => $credits[0]['balance'] ?? 0,
                    ]
                ]
            ];
            $this->zohoBooks->vendorCredits()->applyToBill($credits[0]['vendor_credit_id'] ?? 0, $request);
        } catch (ZohoException $e) {
//            $this->sendEmailToFinanceManager($this->createErrorMessage($this->config['organization'], __METHOD__ . ": vendor credit apply to bill", $package, $request, $e));
        }

        //-------------------- ZohoBooks Smart Parts Online ltd --------------------
        if (!$this->config2['is_enabled'] || !$package->getSupplierProfile()->getZohoSmart() || ($package->getCurrency() == 'USD')) {
            return;
        }
        $supplierProfile = $package->getSupplierProfile();
        try {
            // Create Vendor Credit document for Supplier
            $request = [
                'vendor_id'        => $supplierProfile->getZohoSmart(),
                'reference_number' => $package->getId(),
                'date' => $now->format('Y-m-d'),
                'is_inclusive_tax' => false,
                'notes' => 'Shifting of Liability from WIC to Vendor account via control account',
                'line_items'       => [
                    [
                        'account_id' => $this->config2['control_expense_account'],
                        'name'       => 'Spare parts',
                        'description' => 'Package #'.$package->getFullNumber(),
                        'rate'       => $package->getCostTotal()/100,
                        'quantity'   => 1,
                        'tags' => $tags,
                    ],
                ],
            ];
            $this->zohoBooks2->vendorCredits()->create($request);
        } catch (ZohoException $e) {
            $this->sendEmailToFinanceManager($this->createErrorMessage($this->config2['organization'], __METHOD__ . ": vendor credit create", $package, $request, $e));
        }

        try {
            // Create manual journal #1 entry
            $request = [
                'journal_date' => $now->format('Y-m-d'),
                'currency_id' => $this->config2['currency_id'],
                'reference_number' => $package->getFullNumber(),
                'notes' => 'Returned to Supplier',
                'line_items'       => [
                    [
                        'account_id' => $this->config2['walk_in_customer_prepaid'],
                        'debit_or_credit' => 'credit',
                        'amount' => $package->getGrandTotal()/100,
                    ],
                    [
                        'account_id' => $this->config2['control_expense_account'],
                        'debit_or_credit' => 'debit',
                        'amount' => $package->getGrandTotal()/100,
                    ],
                ],
            ];
            $this->zohoBooks2->journals()->create($request);
        } catch (ZohoException $e) {
            $this->sendEmailToFinanceManager($this->createErrorMessage($this->config2['organization'], __METHOD__ . ": journal create", $package, $request, $e));
        }

        try {
            // Create manual journal #2 entry
            $request = [
                'journal_date' => $now->format('Y-m-d'),
                'currency_id' => $this->config2['currency_id'],
                'reference_number' => $package->getFullNumber(),
                'notes' => 'Returned to Supplier',
                'line_items'       => [
                    [
                        'account_id' => $this->config2['control_expense_account'],
                        'debit_or_credit' => 'debit',
                        'amount' => $package->getFacilitationFee() + $package->getDeliveryTotal()/100,
                    ],
                    [
                        'account_id' => $this->config2['income_facilitation_fees'],
                        'debit_or_credit' => 'credit',
                        'amount' => $package->getFacilitationFee() / 1.15,
                        'customer_id' => $supplierProfile->getZohoSmart(),
                        'tax_id' => $this->config2['service_tax_id'],
                    ],
                    [
                        'account_id' => $this->config2['income_shipping_charges'],
                        'debit_or_credit' => 'credit',
                        'amount' => $package->getDeliveryTotal()/100 / 1.15,
                        'customer_id' => $supplierProfile->getZohoSmart(),
                        'tax_id' => $this->config2['service_tax_id'],
                    ],
                ],
            ];
            $this->zohoBooks2->journals()->create($request);
        } catch (ZohoException $e) {
            $this->sendEmailToFinanceManager($this->createErrorMessage($this->config2['organization'], __METHOD__ . ": journal create", $package, $request, $e));
        }
    }

    public function getZohoBooks()
    {
        return $this->zohoBooks;
    }

    private function sendEmailToFinanceManager($message)
    {
        $ignore_messages = [
            'does not exist',
            'not found in',
            'cannot be applied',
            'already exists',
            'already been created',
        ];

        foreach ($ignore_messages as $ignore_message) {
            if (stristr($message, $ignore_message)) {
                return;
            }
        }

        $mail = $this->emailManager->getEmail();
        $mail->addFrom('no-reply@boodmo.com')
            ->addReplyTo('sales@boodmo.com')
            ->addTo($this->config['finance_email'] ?? 'sales@boodmo.com')
            ->setSubject('Error in Zoho boodmo bot.')
            ->setBody($message);
        $this->emailManager->send($mail);
    }

    /**
     * Calculate ID for ZOHO documents:
     *
     * Sales Invoice = 193-010616/12-1/C
     * Bill for Supplier = 193-010616/12-1/S
     * Bill for Gateway = 193-010616/12-1/G
     * Bill for Logistics = 193-010616/12-1/L
     * Vendor credit for Supplier = 193-010616/12-1/B
     *
     * @param OrderPackage $package
     * @param string       $type
     *
     * @return string
     */
    private function financeNumber(OrderPackage $package, string $type)
    {
        return sprintf(
            '%s/%s',
            $package->getInvoiceNumber(),
            $type
        );
    }

    private function getCreditPointNumber(CreditPoint $creditPoint, string $type, OrderBundle $orderBundle)
    {
        return sprintf(
            '%s-%s/%s',
            $orderBundle->getId(),
            substr($creditPoint->getId(), -1, 4),
            $type
        );
    }

    protected function createErrorMessage($organization, $action, $package, $request, $e)
    {
        $message = "Organization ID: $organization\n" .
            "Finance number: {$package->getInvoiceNumber()}\n" .
            "{$e->getMessage()}\n" .
            "Action: $action\n" .
            "Request:\n" .
            json_encode($request, JSON_PRETTY_PRINT) . "\n";
        return $message;
    }

    protected function getZohoTags(OrderPackage $package)
    {
        $tags = [];

        // AFFILIATE_TAG
        $key = 'AFFILIATE_TAG';
        $tag_id = self::AFFILIATE_TAG;
        if (array_key_exists($key, $this->config) && $this->config[$key]) {
            $tag_id = $this->config[$key];
        }

        $affiliate = $package->getBundle() ? $package->getBundle()->getAffiliate() : OrderBundle::DEFAULT_AFFILIATE;
        $key = 'AFFILIATE_TAG_'.$affiliate;
        $tag_option_id = self::AFFILIATE_OPTIONS[$affiliate] ?? '';
        if (array_key_exists($key, $this->config) && $this->config[$key]) {
            $tag_option_id = $this->config[$key];
        }

        $tags[] = [
            'tag_id' => $tag_id,
            'tag_option_id' => $tag_option_id,
        ];

        // CURRENCY_TAG
        $key = 'CURRENCY_TAG';
        $tag_id = self::CURRENCY_TAG;
        if (array_key_exists($key, $this->config) && $this->config[$key]) {
            $tag_id = $this->config[$key];
        }

        $currency = $package->getCurrency();
        $key = 'CURRENCY_TAG_'.$currency;
        $tag_option_id = self::CURRENCY_OPTIONS[$currency] ?? '';
        if (array_key_exists($key, $this->config) && $this->config[$key]) {
            $tag_option_id = $this->config[$key];
        }

        $tags[] = [
            'tag_id' => $tag_id,
            'tag_option_id' => $tag_option_id,
        ];

        return $tags;
    }

    protected function getZohoCustomFieldTaxInvoiceId($type)
    {
        $field_id = '';
        if (array_key_exists($type, self::TAX_INVOICE)) {
            $field_id = self::TAX_INVOICE[$type];
            $key = "tax_invoice_id_$type"; // $type: bills, invoices
            if (array_key_exists($key, $this->config) && $this->config[$key]) {
                $field_id = $this->config[$key];
            }
        }
        return $field_id;
    }

    /**
     * Get account from settings by CreditPoint type
     * @param CreditPoint $creditPoint
     * @return string
     * @throws \RuntimeException
     */
    private function getAccountIdByCreditPoint(CreditPoint $creditPoint): string
    {
        $result = '';
        switch ($creditPoint->getType()) {
            case CreditPoint::TYPE_PRICE_INCREASED_BY_SUPPLIER:
                $result = $this->config['price_increased_expense'];
                break;
            case CreditPoint::TYPE_CLAIM_ACCEPTED:
                $result = $this->config['customer_claim_accepted'];
                break;
            case CreditPoint::TYPE_TRANSFER_OF_UNAPPLIED_PAYMENT:
                $result = $this->config['gross_sales'];
                break;
        }
        if (empty($result)) {
            throw new \RuntimeException(sprintf('Undefined account for CreditPoint type: %s', $creditPoint->getType()));
        }
        return $result;
    }
}
