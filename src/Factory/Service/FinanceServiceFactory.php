<?php

namespace Boodmo\Sales\Factory\Service;

use Boodmo\Core\Service\SiteSettingService;
use Boodmo\Email\Service\EmailManager;
use Boodmo\Sales\Service\FakeZohoClient;
use Boodmo\Sales\Service\FinanceService;
use Boodmo\Sales\Service\PaymentService;
use Boodmo\Sales\Repository\OrderPackageRepository;
use Boodmo\Shipping\Service\ShippingService;
use Boodmo\User\Service\AddressService;
use OpsWay\ZohoBooks\Api;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Boodmo\Sales\Repository\OrderCreditPointAppliedRepository;

class FinanceServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $settingService = $container->get(SiteSettingService::class);
        $config = $settingService->getSettingsOfTab('zohobooks');
        $emails_for_errors = null;
        if (!empty($config['emails_for_errors'])) {
            $emails_for_errors = array_map('trim', explode(',', $config['emails_for_errors']));
        }
        $config['finance_email'] = $emails_for_errors;
        $zohobooks = $container->build(Api::class, $config);
        $zohobooks->setOrganizationId($config['organization']);

        $config2 = $settingService->getSettingsOfTab('zohobooks2');
        $config2['finance_email'] = $emails_for_errors;
        $zohobooks2 = $container->build(Api::class, $config2);
        $zohobooks2->setOrganizationId($config2['organization']);

        if (!empty($config['test_mode'])) {
            $zohobooks->setClient($container->build(FakeZohoClient::class, $config));
            $zohobooks2->setClient($container->build(FakeZohoClient::class, $config2));
        }

        return new FinanceService(
            $container->get(ShippingService::class),
            $container->get(AddressService::class),
            $container->get(PaymentService::class),
            $container->get(OrderPackageRepository::class),
            $container->get(EmailManager::class),
            $zohobooks,
            $zohobooks2,
            $config,
            $config2,
            $container->get(OrderCreditPointAppliedRepository::class)
        );
    }
}
