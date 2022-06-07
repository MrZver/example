<?php

namespace Boodmo\Sales\Listener;

use Boodmo\Core\Model\ListenerDefinitionProviderInterface;
use Boodmo\Core\Model\ListenerDefinitionProviderTrait;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEvent;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Seo\Service\GuaService;

class GoogleBigQueryObserver implements ListenerDefinitionProviderInterface
{
    use ListenerDefinitionProviderTrait;

    /**
     * @var GuaService
     */
    private $guaService;

    /**
     * GoogleBigQueryObserver constructor.
     *
     * @param GuaService          $guaService
     */
    public function __construct(GuaService $guaService)
    {
        $this->guaService = $guaService;
    }

    public static function collectListeners(): void
    {
        self::addListener(EventEnum::SHIPMENT_RECEIVED, '__invoke');
    }

    /**
     * @param TransitionEvent|TransitionEventInterface $e
     */
    public function __invoke(TransitionEventInterface $e)
    {
        $this->guaService->trackOrder($e->getTarget()->toArray());
    }
}
