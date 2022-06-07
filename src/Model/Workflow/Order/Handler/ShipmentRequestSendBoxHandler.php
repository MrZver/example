<?php

namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Model\Workflow\Order\Command\ShipmentRequestSendBoxCommand;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Service\InvoiceService;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Shipping\Entity\ShippingBox;
use Boodmo\Shipping\Service\ShippingService;
use Boodmo\User\Entity\UserProfile\Supplier;
use Boodmo\User\Service\SupplierService;
use Doctrine\ORM\EntityManager;

final class ShipmentRequestSendBoxHandler
{
    public const ACTIONS = [
        ShippingBox::TYPE_HUB => EventEnum::SHIPPING_SEND_REQUEST_HUB,
        ShippingBox::TYPE_DIRECT => EventEnum::SHIPPING_SEND_REQUEST,
    ];
    /**
     * @var  InvoiceService
     */
    private $invoiceService;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ShippingService
     */
    private $shippingService;
    /**
     * @var OrderService
     */
    private $orderService;
    /**
     * @var SupplierService
     */
    private $supplierService;

    /**
     * ShipmentRequestSendBoxHandler constructor.
     *
     * @param EntityManager   $entityManager
     * @param InvoiceService  $invoiceService
     * @param ShippingService $shippingService
     * @param OrderService    $orderService
     * @param SupplierService $supplierService,
     */
    public function __construct(
        EntityManager $entityManager,
        InvoiceService $invoiceService,
        ShippingService $shippingService,
        OrderService $orderService,
        SupplierService $supplierService
    ) {
        $this->entityManager = $entityManager;
        $this->invoiceService = $invoiceService;
        $this->shippingService = $shippingService;
        $this->orderService = $orderService;
        $this->supplierService = $supplierService;
    }

    /**
     * @param ShipmentRequestSendBoxCommand $command
     * @throws \Exception|\RuntimeException
     */
    public function __invoke(ShipmentRequestSendBoxCommand $command): void
    {
        /* @var ShippingBox $shippingBox */
        /* @var OrderPackage $package */

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $statusWorkflow = $this->orderService->getStatusWorkflow();

            $shippingBox = $this->shippingService->loadShippingBox($command->getShippingBoxId());
            $shippingBox->setTrackNumber($command->getTrackNum());
            $shippingBox->setMethod($command->getShipper());
            $shippingBox->setCashAmount($command->getCashAmount());
            if ($command->getShippingETA()) {
                $shippingBox->setShippingETA($command->getShippingETA());
            }

            foreach ($shippingBox->getPackages() as $package) {
                $package->setInvoiceNumber($package->generateInvoiceNumber());
                if ($package->getSupplierProfile()->getInvoiceNumber()) {
                    $supplier = $package->getSupplierProfile();
                    if ($supplier->getAccountingType() === Supplier::ACCOUNTING_TYPE_AGENT) {
                        $supplier = $supplier->getAccountingAgent();
                    }
                    $package->setExternalInvoice($supplier->getInvoiceNumber()->nextNumber());
                }
                $package->setInvoiceSnapshot($this->invoiceService->getInvoiceSnapshot($package));
                if (!$this->isCorrectInvoiceSum($package)) {
                    throw new \RuntimeException(
                        sprintf(
                            'Order have not fully paid prepaid Bills (order id: %s)',
                            $package->getBundle()->getId()
                        ),
                        422
                    );
                }

                $options = [
                    TransitionEventInterface::CONTEXT => [
                        'author' => $command->getEditor()->getEmail(),
                        'action' => self::ACTIONS[$shippingBox->getType()] ?? '',
                    ]
                ];
                $result = $statusWorkflow->raiseTransition(
                    EventEnum::build(
                        self::ACTIONS[$shippingBox->getType()] ?? '',
                        $statusWorkflow->buildInputItemList($package->getActiveItems()->toArray()),
                        $options
                    )
                );

                $this->supplierService->save($package->getSupplierProfile());
                $this->orderService->save($package->getBundle());
                $this->orderService->triggerNotification($result);
            }

            $shippingBox->resetAdminValidationFlag();
            $this->shippingService->saveShippingBox($shippingBox);

            $this->entityManager->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->entityManager->getConnection()->rollBack();
            throw new \Exception(sprintf('Internal Server Error: %s', $e->getMessage()), $e->getCode(), $e);
        }
    }

    private function isCorrectInvoiceSum(OrderPackage $package)
    {
        // todo: check cost>0 in items?
        $currency = $package->getCurrency();
        $countOpenBills = $package->getBundle()->getBills()->filter(function (OrderBill $bill) use ($currency) {
            return $bill->getCurrency() === $currency
                && $bill->getType() === $bill::TYPE_PREPAID
                && in_array($bill->getStatus(), [$bill::STATUS_OPEN, $bill::STATUS_PARTIALLY_PAID]);
        })->count();
        return $countOpenBills == 0;
    }
}
