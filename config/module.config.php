<?php
namespace Boodmo\Sales;

use Boodmo\Sales\Factory\Plugin\Transactional\OrderConfirmationEmailFactory;
use Boodmo\Sales\Listener;
use Boodmo\Sales\Model\Workflow\Note;
use Boodmo\Sales\Model\Workflow\Order;
use Boodmo\Sales\Model\Workflow\Payment;
use Boodmo\Sales\Model\Workflow\Rma;
use Boodmo\Sales\Model\Workflow\Bids;
use Boodmo\Sales\Plugin\Transactional\OrderConfirmationEmail;

return [
    'session_containers' => [
        'SessionContainer\Checkout',
        'SessionContainer\Cart',
    ],
    'doctrine' => [
        'driver' => [
            'Sales_driver' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'paths' => [__DIR__.'/../src/Entity'],
            ],
            'orm_default' => [
                'drivers' => [
                    __NAMESPACE__.'\Entity' => 'Sales_driver',
                ],
            ],
        ],
    ],
    'docmosis' => [
        "templateName" => "customer_invoice.docx",
        "facilitationTemplateName" => "facilitation_invoice.doc",
        "picklistTemplateName" => "picklist_template.docx",
        "supplierBTemplateName" => "supplier_B_template.docx",
        "supplierSTemplateName" => "facilitation_invoice.doc",
        "accessKey" => "OTYyODAyNDctMGU5OC00MDAyLTg5MjQtYjQwYWZhZGE1ZjA1OjY4NDA2MDA",
        "render_url" => "https://dws2.docmosis.com/services/rs/render"
    ],
    'email_manager' => [
        'transactional' => [
            'factories' => [
                OrderConfirmationEmail::class => OrderConfirmationEmailFactory::class,
            ],
            'aliases'   => [
                OrderConfirmationEmail::TEMPLATE_ID => OrderConfirmationEmail::class,
            ],
        ],
    ],
    'order_workflow_listeners' => [
        Listener\ItemCancelledObserver::class,
        Listener\ZohoBooksObserver::class,
        Listener\CreditMemoObserver::class,
        Listener\GoogleBigQueryObserver::class,
        Listener\ShippingBoxDirectObserver::class,
        Listener\BetaoutObserver::class,
        Listener\AccountingIntegration::class,
    ],
    'prooph' => [
        'service_bus' => [
            'command_bus' => [
                'router' => [
                    'routes' => [
                        //For payments flow
                        Payment\Command\AddCreditPointsCommand::class         => Payment\Handler\AddCreditPointsHandler::class,
                        Payment\Command\EditCreditPointsCommand::class        => Payment\Handler\EditCreditPointsHandler::class,
                        Payment\Command\AddPaymentCommand::class              => Payment\Handler\AddPaymentHandler::class,
                        Payment\Command\AddBillCommand::class                 => Payment\Handler\AddBillHandler::class,
                        Payment\Command\NewCreditMemoCommand::class           => Payment\Handler\NewCreditMemoHandler::class,
                        Payment\Command\EditBillCommand::class                => Payment\Handler\EditBillHandler::class,
                        Payment\Command\EditPaymentCommand::class             => Payment\Handler\EditPaymentHandler::class,
                        Payment\Command\PayToBillCommand::class               => Payment\Handler\PayToBillHandler::class,
                        Payment\Command\ConfirmMemoCommand::class             => Payment\Handler\ConfirmMemoHandler::class,
                        // For notes flow
                        Note\Command\AddNotesCommand::class                   => Note\Handler\AddNotesHandler::class,
                        // For order flow
                        Order\Command\AddItemCommand::class                   => Order\Handler\AddItemHandler::class,
                        Order\Command\CreateRmaCommand::class                 => Order\Handler\CreateRmaHandler::class,
                        Order\Command\ApproveSupplierItemCommand::class       => Order\Handler\ApproveSupplierItemHandler::class,
                        Order\Command\ProcessSupplierBidCommand::class        => Order\Handler\ProcessSupplierBidHandler::class,
                        Order\Command\CancelApproveItemCommand::class         => Order\Handler\CancelApproveItemHandler::class,
                        Order\Command\CancelReasonChangeItemCommand::class    => Order\Handler\CancelReasonChangeItemHandler::class,
                        Order\Command\CancelRequestItemCommand::class         => Order\Handler\CancelRequestItemHandler::class,
                        Order\Command\CancelRequestSupplierItemCommand::class => Order\Handler\CancelRequestSupplierItemHandler::class,
                        Order\Command\EditItemCommand::class                  => Order\Handler\EditItemHandler::class,
                        Order\Command\KeepProcessingItemCommand::class        => Order\Handler\KeepProcessingItemHandler::class,
                        Order\Command\NewBundleCommand::class                 => Order\Handler\NewBundleHandler::class,
                        Order\Command\ConvertCurrencyPackageCommand::class    => Order\Handler\ConvertCurrencyPackageHandler::class,
                        Order\Command\PackedItemsCommand::class               => Order\Handler\PackedItemsHandler::class,
                        Order\Command\ReplaceItemCommand::class               => Order\Handler\ReplaceItemHandler::class,
                        Order\Command\ShipmentDeliveryBoxCommand::class       => Order\Handler\ShipmentDeliveryBoxHandler::class,
                        Order\Command\ShipmentDeniedPackageCommand::class     => Order\Handler\ShipmentDeniedPackageHandler::class,
                        Order\Command\ShipmentDispatchBoxCommand::class       => Order\Handler\ShipmentDispatchBoxHandler::class,
                        Order\Command\ShipmentRejectBoxCommand::class         => Order\Handler\ShipmentRejectBoxHandler::class,
                        Order\Command\ShipmentRequestSendBoxCommand::class    => Order\Handler\ShipmentRequestSendBoxHandler::class,
                        Order\Command\ShipmentReturnPackageCommand::class     => Order\Handler\ShipmentReturnPackageHandler::class,
                        Order\Command\SplitItemCommand::class                 => Order\Handler\VendorChangeItemHandler::class,
                        Order\Command\SupplierConfirmItemCommand::class       => Order\Handler\SupplierConfirmItemHandler::class,
                        Order\Command\SupplierHubReadyShippingCommand::class  => Order\Handler\SupplierHubReadyShippingHandler::class,
                        Order\Command\SupplierReadyDeliveryItemCommand::class => Order\Handler\SupplierReadyDeliveryItemHandler::class,
                        Order\Command\SupplierRejectBoxCommand::class         => Order\Handler\SupplierRejectBoxHandler::class,
                        Order\Command\VendorChangeItemCommand::class          => Order\Handler\VendorChangeItemHandler::class,
                        Order\Command\WarehouseInItemsCommand::class          => Order\Handler\WarehouseInItemsHandler::class,
                        Order\Command\SetFlagsCommand::class                  => Order\Handler\SetFlagsHandler::class,
                        Order\Command\SnoozeConfirmDateItemCommand::class     => Order\Handler\SnoozeConfirmDateItemHandler::class,
                        Order\Command\AskForCourierCommand::class             => Order\Handler\AskForCourierHandler::class,
                        // For Rma
                        Rma\Command\ChangeStatusRmaCommand::class             => Rma\Handler\ChangeStatusRmaHandler::class,
                        // For Bids
                        Bids\Command\MissAskCommand::class                    => Bids\Handler\MissAskHandler::class,
                        Bids\Command\CancelBidCommand::class                  => Bids\Handler\CancelBidHandler::class,
                    ],
                ],
            ],
        ],
    ],
];
