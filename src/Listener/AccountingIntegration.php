<?php

namespace Boodmo\Sales\Listener;

use Boodmo\Accounting\DTO\CustomerDTO;
use Boodmo\Accounting\DTO\ItemDTO;
use Boodmo\Accounting\DTO\OrderDTO;
use Boodmo\Accounting\Event\EventError;
use Boodmo\Accounting\Model\Accounting;
use Boodmo\Catalog\Service\PartService;
use Boodmo\Catalog\Service\SupplierPartService;
use Boodmo\Core\Model\ListenerDefinitionProviderInterface;
use Boodmo\Core\Model\ListenerDefinitionProviderTrait;
use Boodmo\Core\Service\SiteSettingService;
use Boodmo\Email\Service\EmailManager;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\User\Entity\UserProfile\Supplier;
use Closure;

class AccountingIntegration implements ListenerDefinitionProviderInterface
{
    use ListenerDefinitionProviderTrait;

    /** @var Accounting */
    private $accounting;

    /** @var PartService */
    private $partService;

    /** @var SupplierPartService */
    private $supplierPartService;

    /** @var EmailManager */
    private $emailManager;
    /**
     * @var SiteSettingService
     */
    private $settingService;

    public function __construct(
        Accounting $accounting,
        SiteSettingService $settingService,
        PartService $partService,
        SupplierPartService $supplierPartService,
        EmailManager $emailManager
    ) {
        $this->accounting = $accounting;
        $this->settingService = $settingService;
        $this->partService = $partService;
        $this->supplierPartService = $supplierPartService;
        $this->emailManager = $emailManager;
        $this->accounting->setErrorHandler(Closure::fromCallable([$this, 'sendErrorMessage']));
    }

    public static function collectListeners(): void
    {
        self::addListener(EventEnum::SHIPMENT_ACCEPT, 'dispatched');
    }

    /**
     * @param TransitionEventInterface $e
     */
    public function dispatched(TransitionEventInterface $e): void
    {
        /** @var $orderPackage OrderPackage */
        $orderPackage = $e->getTarget()->toArray()[0]->getPackage();
        $supplier     = $agent = $orderPackage->getSupplierProfile();

        $config = $supplier->getAccounting();
        unset($config[Supplier::ACCOUNTING_TYPE_SELF]);
        if ($supplier->getAccountingType() === Supplier::ACCOUNTING_TYPE_SELF
            || $supplier->getAccountingType() === Supplier::ACCOUNTING_TYPE_AGENT) {
            if ($supplier->getAccountingType() === Supplier::ACCOUNTING_TYPE_AGENT) {
                $agent = $supplier->getAccountingAgent();
            }
            $config[Supplier::ACCOUNTING_TYPE_SELF] = $agent->getAccounting()[Supplier::ACCOUNTING_TYPE_SELF];
        }
        $config['boodmo'] = $this->settingService->getSettingsOfTab('zohobooks');

        $this->accounting->orderDispatch($this->convertPackageToOrderAccounting($orderPackage), $config);
    }

    private function convertPackageToOrderAccounting(OrderPackage $package): OrderDTO
    {
        $items = [];
        foreach ($package->getActiveItems() as $item) {
            $part = $this->partService->loadPart($item->getPartId());
            $supplierPart = $this->supplierPartService->loadSupplierPart($item->getProductId());
            $sku = $part->getSku();
            $items[] = new ItemDTO(
                $sku,
                $item->getPrice() / 100,
                $item->getQty(),
                $supplierPart ? $supplierPart->getMrp() : 0,
                $item->getBrand(),
                $item->getNumber(),
                $part->getAttributes()['description'] ?? $part->getName()
            );
        }
        $bundle = $package->getBundle();
        $customer = new CustomerDTO(
            $bundle->getCustomerAddress()['first_name'] . ' ' . $bundle->getCustomerAddress()['last_name'],
            $bundle->getCustomerAddress()['state'],
            $bundle->getCustomerAddress()['pin']
        );

        return new OrderDTO(
            $items,
            $customer,
            $package->getFullNumber(),
            $package->getExternalInvoice(),
            $package->getInvoiceNumber(),
            $package->getGrandTotal()/100,
            $package->getDeliveryDays(),
            $bundle->getAffiliate(),
            $package->getCurrency()
        );
    }

    private function sendErrorMessage(EventError $error)
    {
        $message = "{$error->getException()->getMessage()}\n" .
            json_encode($error->getRequest(), JSON_PRETTY_PRINT) . "\n";
        $mail = $this->emailManager->getEmail();
        $mail->addFrom('no-reply@boodmo.com')
            ->addTo('boodmo@opsway.com')
            ->setSubject('Accounting error')
            ->setBody($message);
        $this->emailManager->send($mail);
    }
}
