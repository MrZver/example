<?php

namespace Boodmo\Sales\Factory\Plugin\Transactional;

use Boodmo\Core\Service\SiteSettingService;
use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Plugin\Transactional\OrderConfirmationEmail;
use Boodmo\Sales\Service\PaymentService;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class OrderConfirmationEmailFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new OrderConfirmationEmail(
            $container->get(SiteSettingService::class),
            $container->get(PaymentService::class),
            $container->get(MoneyService::class)
        );
    }
}
