<?php

namespace Boodmo\Sales;

use Boodmo\Accounting\Model\Accounting;
use Boodmo\Catalog\Service\PartService;
use Boodmo\Catalog\Service\SupplierPartService;
use Boodmo\Core\Factory\Repository\RepositoryFactory;
use Boodmo\Core\Repository\SiteSettingRepository;
use Boodmo\Core\Service\SiteSettingService;
use Boodmo\Currency\Service\MoneyService;
use Boodmo\Email\Service\EmailManager;
use Boodmo\Media\Service\MediaService;
use Boodmo\Sales\Entity\CancelReason;
use Boodmo\Sales\Entity\Cart;
use Boodmo\Sales\Entity\CreditMemo;
use Boodmo\Sales\Entity\CreditPoint;
use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderCreditPointApplied;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Entity\OrderPaymentApplied;
use Boodmo\Sales\Entity\OrderRma;
use Boodmo\Sales\Entity\OrderBid;
use Boodmo\Sales\Entity\Taxes;
use Boodmo\Sales\Entity\Trigger;
use Boodmo\Sales\Entity\Payment as PaymentEntity;
use Boodmo\Sales\Model\Checkout\InputFilterList;
use Boodmo\Sales\Model\Workflow\Note;
use Boodmo\Sales\Model\Workflow\Order;
use Boodmo\Sales\Model\Workflow\Payment;
use Boodmo\Sales\Model\Workflow\Rma;
use Boodmo\Sales\Model\Workflow\Bids;
use Boodmo\Sales\Model\Workflow\StatusWorkflow;
use Boodmo\Sales\Repository\CancelReasonRepository;
use Boodmo\Sales\Repository\OrderBundleRepository;
use Boodmo\Sales\Repository\OrderItemRepository;
use Boodmo\Sales\Repository\OrderPackageRepository;
use Boodmo\Sales\Repository\OrderRmaRepository;
use Boodmo\Sales\Repository\OrderBidRepository;
use Boodmo\Sales\Service\FinanceService;
use Boodmo\Sales\Service\InvoiceService;
use Boodmo\Sales\Service\NotificationService;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Sales\Service\PaymentService;
use Boodmo\Sales\Service\SalesService;
use Boodmo\Sales\Service\TaxesService;
use Boodmo\Seo\Service\BetaoutService;
use Boodmo\Seo\Service\GuaService;
use Boodmo\Shipping\Repository\CountryRepository;
use Boodmo\Shipping\Service\DeliveryFinderService;
use Boodmo\Shipping\Service\ShippingService;
use Boodmo\User\Hydrator\SupplierHydrator;
use Boodmo\User\Service\AddressService;
use Boodmo\User\Service\CustomerService;
use Boodmo\User\Service\SupplierService;
use Boodmo\User\Service\UserService;
use Doctrine\ORM\EntityManager;
use Prooph\ServiceBus\CommandBus;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\Proxy\LazyServiceFactory;

return [
    ConfigAbstractFactory::class => [
        Service\CheckoutService::class => [
            UserService::class,
            SalesService::class,
            SupplierService::class,
            PartService::class,
            CommandBus::class,
            OrderService::class,
            PaymentService::class,
            InputFilterList::class,
            Repository\CartRepository::class,
            ShippingService::class,
        ],
        Note\Handler\AddNotesHandler::class                   => [
            EntityManager::class,
        ],
        Order\Handler\SetFlagsHandler::class                  => [
            EntityManager::class,
        ],
        Rma\Handler\ChangeStatusRmaHandler::class             => [
            OrderRmaRepository::class,
            BetaoutService::class,
        ],
        Bids\Handler\MissAskHandler::class                    => [
            OrderService::class,
            OrderBidRepository::class,
            SupplierService::class,
        ],
        Bids\Handler\CancelBidHandler::class                    => [
            OrderBidRepository::class,
        ],
        Payment\Handler\AddCreditPointsHandler::class         => [
            MoneyService::class,
            FinanceService::class,
            OrderService::class,
            CustomerService::class,
            PaymentService::class,
        ],
        Payment\Handler\EditCreditPointsHandler::class        => [
            MoneyService::class,
            PaymentService::class,
        ],
        Payment\Handler\AddPaymentHandler::class              => [
            MoneyService::class,
            PaymentService::class,
            FinanceService::class,
            OrderService::class,
            CustomerService::class,
            EntityManager::class
        ],
        Payment\Handler\AddBillHandler::class                 => [
            MoneyService::class,
            PaymentService::class,
            OrderService::class
        ],
        Payment\Handler\NewCreditMemoHandler::class           => [
            MoneyService::class,
            PaymentService::class,
            OrderService::class
        ],
        Payment\Handler\EditBillHandler::class                => [
            MoneyService::class,
            PaymentService::class,
        ],
        Payment\Handler\PayToBillHandler::class               => [
            PaymentService::class,
            FinanceService::class,
        ],
        Payment\Handler\EditPaymentHandler::class             => [
            PaymentService::class,
            MoneyService::class,
        ],
        Payment\Handler\ConfirmMemoHandler::class           => [
            MoneyService::class,
            PaymentService::class,
            OrderService::class
        ],
        Order\Handler\AddItemHandler::class                   => [
            SupplierPartService::class,
            OrderService::class,
        ],
        Order\Handler\ApproveSupplierItemHandler::class       => [
            OrderService::class,
        ],
        Order\Handler\ProcessSupplierBidHandler::class       => [
            OrderService::class,
            OrderBidRepository::class,
            SupplierService::class,
            ShippingService::class,
            SiteSettingService::class,
            MoneyService::class,
            CommandBus::class,
            SupplierPartService::class,
        ],
        Order\Handler\CreateRmaHandler::class                 => [
            OrderService::class,
        ],
        Order\Handler\CancelApproveItemHandler::class         => [
            OrderService::class,
        ],
        Order\Handler\CancelReasonChangeItemHandler::class    => [
            OrderService::class,
        ],
        Order\Handler\CancelRequestItemHandler::class         => [
            OrderService::class,
        ],
        Order\Handler\CancelRequestSupplierItemHandler::class => [
            SupplierPartService::class,
            OrderService::class,
            OrderBidRepository::class,
        ],
        Order\Handler\EditItemHandler::class                  => [
            OrderService::class,
            SupplierPartService::class,
        ],
        Order\Handler\KeepProcessingItemHandler::class        => [
            OrderService::class,
        ],
        Order\Handler\NewBundleHandler::class                 => [
            OrderService::class,
            Service\CheckoutService::class,
            EntityManager::class,
            CustomerService::class,
            PaymentService::class,
            BetaoutService::class,
        ],
        Order\Handler\PackedItemsHandler::class               => [
            EntityManager::class,
            ShippingService::class,
            OrderService::class,
        ],
        Order\Handler\ReplaceItemHandler::class               => [
            SupplierPartService::class,
            OrderService::class,
            PartService::class,
        ],
        Order\Handler\ConvertCurrencyPackageHandler::class     => [
            MoneyService::class,
            OrderService::class,
            SupplierService::class,
        ],
        Order\Handler\ShipmentDeliveryBoxHandler::class       => [
            EntityManager::class,
            ShippingService::class,
            OrderService::class,
        ],
        Order\Handler\ShipmentDeniedPackageHandler::class     => [
            OrderService::class,
        ],
        Order\Handler\ShipmentDispatchBoxHandler::class       => [
            EntityManager::class,
            ShippingService::class,
            OrderService::class,
        ],
        Order\Handler\ShipmentRejectBoxHandler::class         => [
            EntityManager::class,
            ShippingService::class,
            OrderService::class,
        ],
        Order\Handler\ShipmentRequestSendBoxHandler::class    => [
            EntityManager::class,
            InvoiceService::class,
            ShippingService::class,
            OrderService::class,
            SupplierService::class,
        ],
        Order\Handler\ShipmentReturnPackageHandler::class     => [
            OrderService::class,
        ],
        Order\Handler\SupplierConfirmItemHandler::class       => [
            OrderService::class,
        ],
        Order\Handler\SupplierHubReadyShippingHandler::class  => [
            OrderService::class,
        ],
        Order\Handler\SupplierReadyDeliveryItemHandler::class => [
            OrderService::class,
        ],
        Order\Handler\SupplierRejectBoxHandler::class         => [
            EntityManager::class,
            ShippingService::class,
            OrderService::class,
        ],
        Order\Handler\VendorChangeItemHandler::class          => [
            SupplierService::class,
            SupplierPartService::class,
            OrderService::class,
            PartService::class
        ],
        Order\Handler\WarehouseInItemsHandler::class          => [
            OrderService::class,
        ],
        Order\Handler\SnoozeConfirmDateItemHandler::class     => [
            OrderService::class,
        ],
        Order\Handler\AskForCourierHandler::class     => [
            EntityManager::class,
            ShippingService::class,
            OrderService::class,
        ],
        Service\SalesService::class => [
            SupplierPartService::class,
            DeliveryFinderService::class,
            MoneyService::class,
            SupplierService::class,
            CountryRepository::class,
            MediaService::class,
        ],
        Service\TaxesService::class          => [
            Repository\TaxesRepository::class,
        ],
        Listener\CreditMemoObserver::class          => [
            Service\OrderService::class,
            Service\NotificationService::class,
            SiteSettingService::class,
        ],
        Listener\GoogleBigQueryObserver::class          => [
            GuaService::class,
        ],
        Listener\ItemCancelledObserver::class          => [
            GuaService::class,
            SupplierPartService::class,
        ],
        Listener\ShippingBoxDirectObserver::class          => [
            ShippingService::class,
        ],
        Listener\ZohoBooksObserver::class          => [
            Service\FinanceService::class,
        ],
        Listener\BetaoutObserver::class          => [
            BetaoutService::class,
        ],
        Listener\AccountingIntegration::class => [
            Accounting::class,
            SiteSettingService::class,
            PartService::class,
            SupplierPartService::class,
            EmailManager::class
        ],
        Hydrator\OrderBundleHydrator::class             => [
            MoneyService::class,
            Hydrator\OrderPackageHydrator::class,
            Hydrator\OrderCreditPointAppliedHydrator::class,
        ],
        Hydrator\OrderPackageHydrator::class            => [
            MoneyService::class,
            Hydrator\OrderItemHydrator::class,
            SupplierHydrator::class,
            ShippingService::class
        ],
        Hydrator\OrderItemHydrator::class               => [
            MoneyService::class,
        ],
        Hydrator\OrderCreditPointAppliedHydrator::class => [

        ],
        Service\OrderService::class => [
            OrderBundleRepository::class,
            OrderPackageRepository::class,
            SupplierService::class,
            UserService::class,
            OrderItemRepository::class,
            SiteSettingRepository::class,
            ShippingService::class,
            AddressService::class,
            'Config',
            CancelReasonRepository::class,
            NotificationService::class,
            StatusWorkflow::class,
            CommandBus::class,
            MoneyService::class,
            OrderRmaRepository::class,
            SiteSettingService::class
        ],
        Service\InvoiceService::class => [
            'Config',
            EntityManager::class,
            MediaService::class,
            AddressService::class,
            TaxesService::class,
            MoneyService::class
        ],
    ],
    RepositoryFactory::class => [
        Repository\OrderBundleRepository::class             => OrderBundle::class,
        Repository\OrderPackageRepository::class            => OrderPackage::class,
        Repository\OrderItemRepository::class               => OrderItem::class,
        Repository\CancelReasonRepository::class            => CancelReason::class,
        Repository\TriggerRepository::class                 => Trigger::class,
        Repository\TaxesRepository::class                   => Taxes::class,
        Repository\PaymentRepository::class                 => PaymentEntity::class,
        Repository\CreditMemoRepository::class              => CreditMemo::class,
        Repository\CreditPointRepository::class             => CreditPoint::class,
        Repository\OrderCreditPointAppliedRepository::class => OrderCreditPointApplied::class,
        Repository\OrderPaymentAppliedRepository::class     => OrderPaymentApplied::class,
        Repository\OrderBillRepository::class               => OrderBill::class,
        Repository\CartRepository::class                    => Cart::class,
        Repository\OrderRmaRepository::class                => OrderRma::class,
        Repository\OrderBidRepository::class                => OrderBid::class,
    ],
    'service_manager'            => [
        'factories'     => [
            InputFilterList::class                          => InvokableFactory::class,
            Service\OrderService::class                     => ConfigAbstractFactory::class,
            Service\SalesService::class                     => ConfigAbstractFactory::class,
            Service\CheckoutService::class                  => ConfigAbstractFactory::class,
            Service\NotificationService::class              => Factory\Service\NotificationServiceFactory::class,
            Service\TaxesService::class                     => ConfigAbstractFactory::class,
            Service\PaymentService::class                   => Factory\Service\PaymentServiceFactory::class,
            Service\FinanceService::class                   => Factory\Service\FinanceServiceFactory::class,
            Service\InvoiceService::class                   => ConfigAbstractFactory::class,
            Service\FakeZohoClient::class                   => InvokableFactory::class,
            Listener\CreditMemoObserver::class              => ConfigAbstractFactory::class,
            Listener\GoogleBigQueryObserver::class          => ConfigAbstractFactory::class,
            Listener\ItemCancelledObserver::class           => ConfigAbstractFactory::class,
            Listener\ShippingBoxDirectObserver::class       => ConfigAbstractFactory::class,
            Listener\ZohoBooksObserver::class               => ConfigAbstractFactory::class,
            Listener\BetaoutObserver::class                 => ConfigAbstractFactory::class,
            Listener\AccountingIntegration::class           => ConfigAbstractFactory::class,
            Repository\OrderBundleRepository::class         => RepositoryFactory::class,
            Repository\OrderPackageRepository::class        => RepositoryFactory::class,
            Repository\OrderItemRepository::class           => RepositoryFactory::class,
            Repository\CancelReasonRepository::class        => RepositoryFactory::class,
            Repository\TriggerRepository::class             => RepositoryFactory::class,
            Repository\TaxesRepository::class               => RepositoryFactory::class,
            Repository\PaymentRepository::class             => RepositoryFactory::class,
            Repository\CreditMemoRepository::class          => RepositoryFactory::class,
            Repository\CreditPointRepository::class         => RepositoryFactory::class,
            Repository\OrderCreditPointAppliedRepository::class => RepositoryFactory::class,
            Repository\OrderPaymentAppliedRepository::class => RepositoryFactory::class,
            Repository\OrderBillRepository::class           => RepositoryFactory::class,
            Repository\CartRepository::class                => RepositoryFactory::class,
            Repository\OrderRmaRepository::class            => RepositoryFactory::class,
            Repository\OrderBidRepository::class            => RepositoryFactory::class,
            Model\Workflow\StatusWorkflow::class            => Factory\StatusWorkflowFactory::class,
            Order\Handler\ConvertCurrencyPackageHandler::class => ConfigAbstractFactory::class,
            Hydrator\OrderBundleHydrator::class             => ConfigAbstractFactory::class,
            Hydrator\OrderPackageHydrator::class            => ConfigAbstractFactory::class,
            Hydrator\OrderItemHydrator::class               => ConfigAbstractFactory::class,
            Hydrator\OrderCreditPointAppliedHydrator::class => InvokableFactory::class,
        ],
        'lazy_services' => [
            'class_map' => [
                Service\FinanceService::class  => Service\FinanceService::class,
                Service\CheckoutService::class => Service\CheckoutService::class,
            ],
        ],
        'delegators'    => [
            Service\FinanceService::class  => [
                LazyServiceFactory::class,
            ],
            Service\CheckoutService::class => [
                LazyServiceFactory::class,
            ],
        ],
    ],
];
