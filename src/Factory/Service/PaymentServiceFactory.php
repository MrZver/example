<?php

namespace Boodmo\Sales\Factory\Service;

use Boodmo\Core\Service\SiteSettingService;
use Boodmo\Email\Service\EmailManager;
use Boodmo\Sales\Model\Payment\Provider\CashProvider;
use Boodmo\Sales\Model\Payment\Provider\CheckoutProvider;
use Boodmo\Sales\Model\Payment\Provider\HdfcBankProvider;
use Boodmo\Sales\Model\Payment\Provider\HdfcProvider;
use Boodmo\Sales\Model\Payment\Provider\PayPalProvider;
use Boodmo\Sales\Model\Payment\Provider\RazorPayProvider;
use Boodmo\Sales\Repository\CreditPointRepository;
use Boodmo\Sales\Repository\OrderBillRepository;
use Boodmo\Sales\Repository\OrderCreditPointAppliedRepository;
use Boodmo\Sales\Repository\OrderPaymentAppliedRepository;
use Boodmo\Sales\Repository\PaymentRepository;
use Boodmo\Sales\Service\FinanceService;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Sales\Service\PaymentService;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Prooph\ServiceBus\CommandBus;

class PaymentServiceFactory implements FactoryInterface
{
    public const PROVIDERS = [
        CashProvider::class,
        CheckoutProvider::class,
        HdfcBankProvider::class,
        HdfcProvider::class,
        PayPalProvider::class,
        RazorPayProvider::class,
    ];
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get(SiteSettingService::class)->getSettingsOfTab('payments');
        return new PaymentService(
            self::PROVIDERS,
            $config,
            $container->get(PaymentRepository::class),
            $container->get(FinanceService::class),
            $container->get(OrderService::class),
            $container->get(EmailManager::class),
            $container->get(CommandBus::class),
            $container->get(OrderBillRepository::class),
            $container->get(CreditPointRepository::class),
            $container->get(OrderPaymentAppliedRepository::class),
            $container->get(OrderCreditPointAppliedRepository::class),
            $container->get('Boodmo\Core\ErrorLogger')
        );
    }
}
