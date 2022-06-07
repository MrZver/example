<?php

namespace Boodmo\Sales\Listener;

use Boodmo\Core\Model\ListenerDefinitionProviderInterface;
use Boodmo\Core\Model\ListenerDefinitionProviderTrait;
use Boodmo\Core\Service\SiteSettingService;
use Boodmo\Sales\Entity\CreditMemo;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEvent;
use Boodmo\Sales\Service\NotificationService;
use Boodmo\Sales\Service\OrderService;
use Doctrine\DBAL\Exception\InvalidArgumentException;

class CreditMemoObserver implements ListenerDefinitionProviderInterface
{
    use ListenerDefinitionProviderTrait;
    /**
     * @var NotificationService
     */
    private $notificationService;

    /**
     * @var SiteSettingService
     */
    private $settingsService;

    const CREDIT_MEMO_TEMPLATE_ID = 'finance-creditmemo';

    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * CreditMemoObserver constructor.
     *
     * @param OrderService        $orderService
     * @param NotificationService $notificationService
     * @param SiteSettingService  $settingService
     */
    public function __construct(
        OrderService $orderService,
        NotificationService $notificationService,
        SiteSettingService $settingService
    ) {
        $this->orderService = $orderService;
        $this->notificationService = $notificationService;
        $this->settingsService = $settingService;
    }

    public static function collectListeners(): void
    {
        self::addListener(EventEnum::SHIPMENT_RECEIVED, '__invoke', -10);
        self::addListener(EventEnum::SHIPMENT_REJECT, '__invoke', -10);
        self::addListener(EventEnum::CUSTOMER_CANCEL_APPROVE, '__invoke', -10);
        self::addListener(EventEnum::CANCEL_NOT_PAID, '__invoke', -10);
        self::addListener(EventEnum::CANCEL_PROCESSING_USER, '__invoke', -10);
    }

    /**
     * @param TransitionEvent $e
     */
    public function __invoke(TransitionEvent $e)
    {
    }
}
