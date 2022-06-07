<?php

namespace Boodmo\SalesTest\Factory\Service;

use Boodmo\Core\Service\SiteSettingService;
use Boodmo\Email\Service\EmailManager;
use Boodmo\Sales\Factory\Service\PaymentServiceFactory;
use Boodmo\Sales\Repository\CreditPointRepository;
use Boodmo\Sales\Repository\OrderBillRepository;
use Boodmo\Sales\Repository\OrderCreditPointAppliedRepository;
use Boodmo\Sales\Repository\OrderPaymentAppliedRepository;
use Boodmo\Sales\Repository\PaymentRepository;
use Boodmo\Sales\Service\FinanceService;
use Boodmo\Sales\Service\OrderService;
use Boodmo\Sales\Service\PaymentService;
use Interop\Container\ContainerInterface;
use Prooph\ServiceBus\CommandBus;
use Zend\Log\Logger;

class PaymentServiceFactoryTest extends \PHPUnit\Framework\TestCase
{
    private $locator;
    /**
     * @var PaymentServiceFactory
     */
    private $factory;

    protected function setUp()
    {
        $this->factory = new PaymentServiceFactory();
    }

    public function testInvoke()
    {
        $this->locator = $this->createMock(ContainerInterface::class);

        $siteSettingService = $this->createMock(SiteSettingService::class);
        $siteSettingService->expects($this->any())
            ->method('getSettingsOfTab')
            ->with('payments')
            ->willReturn([]);

        $this->locator->expects($this->at(0))
            ->method('get')
            ->with(SiteSettingService::class)
            ->will($this->returnValue($siteSettingService));

        $this->locator->expects($this->at(1))
            ->method('get')
            ->with(PaymentRepository::class)
            ->will($this->returnValue($this->createMock(PaymentRepository::class)));

        $this->locator->expects($this->at(2))
            ->method('get')
            ->with(FinanceService::class)
            ->will($this->returnValue($this->createMock(FinanceService::class)));

        $this->locator->expects($this->at(3))
            ->method('get')
            ->with(OrderService::class)
            ->will($this->returnValue($this->createMock(OrderService::class)));

        $this->locator->expects($this->at(4))
            ->method('get')
            ->with(EmailManager::class)
            ->will($this->returnValue($this->createMock(EmailManager::class)));

        $this->locator->expects($this->at(5))
            ->method('get')
            ->with(CommandBus::class)
            ->will($this->returnValue($this->createMock(CommandBus::class)));

        $this->locator->expects($this->at(6))
            ->method('get')
            ->with(OrderBillRepository::class)
            ->will($this->returnValue($this->createMock(OrderBillRepository::class)));

        $this->locator->expects($this->at(7))
            ->method('get')
            ->with(CreditPointRepository::class)
            ->will($this->returnValue($this->createMock(CreditPointRepository::class)));

        $this->locator->expects($this->at(8))
            ->method('get')
            ->with(OrderPaymentAppliedRepository::class)
            ->will($this->returnValue($this->createMock(OrderPaymentAppliedRepository::class)));

        $this->locator->expects($this->at(9))
            ->method('get')
            ->with(OrderCreditPointAppliedRepository::class)
            ->will($this->returnValue($this->createMock(OrderCreditPointAppliedRepository::class)));

        $this->locator->expects($this->at(10))
            ->method('get')
            ->with('Boodmo\Core\ErrorLogger')
            ->will($this->returnValue($this->createMock(Logger::class)));

        $this->assertInstanceOf(
            PaymentService::class,
            $this->factory->__invoke($this->locator, PaymentService::class)
        );
    }
}
