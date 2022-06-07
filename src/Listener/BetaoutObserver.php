<?php

namespace Boodmo\Sales\Listener;

use Boodmo\Core\Model\ListenerDefinitionProviderInterface;
use Boodmo\Core\Model\ListenerDefinitionProviderTrait;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\TransitionEvent;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Seo\Service\BetaoutService;
use Psr\Log\InvalidArgumentException;

class BetaoutObserver implements ListenerDefinitionProviderInterface
{
    use ListenerDefinitionProviderTrait;

    /**
     * @var array
     */
    private $betaoutService;

    public function __construct(BetaoutService $betaoutService)
    {
        $this->betaoutService = $betaoutService;
    }

    public static function collectListeners(): void
    {
        self::addListener(EventEnum::SHIPMENT_RECEIVED, '__invoke', -10);
        self::addListener(EventEnum::NEW_ORDER, 'clearCart', -10);
        //self::addListener(EventEnum::NEW_ORDER, 'orderPlace', -10);
    }

    /**
     * @param TransitionEventInterface|TransitionEvent $e
     *
     * @throws InvalidArgumentException
     */
    public function __invoke(TransitionEventInterface $e)
    {
        if ($this->betaoutService->isBetaoutEnabled()) {
            $this->betaoutService->processItemsPurchase($e->getTarget()->toArray());
        }
    }

    public function clearCart(TransitionEventInterface $e)
    {
        if ($this->betaoutService->isBetaoutEnabled()) {
            $this->betaoutService->processClearCart($e->getTarget()->toArray());
        }
    }

    /*public function orderPlace(TransitionEventInterface $e)
    {
        if ($this->betaoutService->isBetaoutEnabled()) {
            $this->betaoutService->processOrderPlace($e->getTarget()->toArray());
        }
    }*/
}
