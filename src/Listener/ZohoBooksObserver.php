<?php

namespace Boodmo\Sales\Listener;

use Boodmo\Core\Model\ListenerDefinitionProviderInterface;
use Boodmo\Core\Model\ListenerDefinitionProviderTrait;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Service\FinanceService;
use Boodmo\Sales\Service\InvoiceService;

class ZohoBooksObserver implements ListenerDefinitionProviderInterface
{
    use ListenerDefinitionProviderTrait;

    /**
     * @var FinanceService
     */
    private $financeService;

    public function __construct(FinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    public static function collectListeners(): void
    {
        self::addListener(EventEnum::SHIPMENT_ACCEPT, 'dispatched');
        self::addListener(EventEnum::SHIPMENT_RECEIVED, 'delivered');
        self::addListener(EventEnum::SHIPMENT_REJECT, 'rejected');
        self::addListener(EventEnum::SHIPMENT_DENY, 'denied');
        self::addListener(EventEnum::SHIPMENT_RETURN, 'returnedToSupplier');
    }

    /**
     * @param TransitionEventInterface $e
     */
    public function dispatched(TransitionEventInterface $e): void
    {
        /**
         * @var $orderPackage OrderPackage
         */
        $orderPackage = $e->getTarget()->toArray()[0]->getPackage();
        $this->financeService->shippingDispatchedObserver($orderPackage);
    }

    /**
     * @param TransitionEventInterface $e
     */
    public function delivered(TransitionEventInterface $e)
    {
        /**
         * @var $orderPackage OrderPackage
         */
        $orderPackage = $e->getTarget()->toArray()[0]->getPackage();
        $this->financeService->shippingDeliveredObserver($orderPackage);
    }

    /**
     * @param TransitionEventInterface $e
     */
    public function rejected(TransitionEventInterface $e)
    {
        /*
         * @var $orderPackage OrderPackage
         */
        $orderPackage = $e->getTarget()->toArray()[0]->getPackage();
        $this->financeService->shippingRejectedObserver($orderPackage);
    }

    /**
     * @param TransitionEventInterface $e
     */
    public function denied(TransitionEventInterface $e)
    {
        /*
         * @var $orderPackage OrderPackage
         */
        $orderPackage = $e->getTarget()->toArray()[0]->getPackage();
        $this->financeService->shippingDeniedObserver($orderPackage);
    }


    /**
     * @param TransitionEventInterface $e
     */
    public function returnedToSupplier(TransitionEventInterface $e)
    {
        /*
         * @var $orderPackage OrderPackage
         */
        $orderPackage = $e->getTarget()->toArray()[0]->getPackage();
        $this->financeService->shippingReturnedToSupplierObserver($orderPackage);
    }
}
