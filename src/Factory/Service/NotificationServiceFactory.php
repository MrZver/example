<?php

namespace Boodmo\Sales\Factory\Service;

use Boodmo\Frontend\Base\View\Helper\FormatPrice;
use Boodmo\Email\Service\EmailManager;
use Boodmo\Sales\Service\NotificationService;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Shipping\Service\ShippingService;
use SlmMail\Service\MandrillService;
use Boodmo\Email\Service\Template\MandrillService as TemplateService;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class NotificationServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new NotificationService(
            $container->get(MandrillService::class),
            $container->get(TemplateService::class),
            $container->get(EmailManager::class),
            $container->get(ShippingService::class),
            $container->get('Config'),
            $container->get('doctrine.entitymanager.orm_default'),
            $container->get('ViewHelperManager')->get(FormatPrice::class),
            $container->get('Boodmo\Core\ErrorLogger')
        );
    }
}
