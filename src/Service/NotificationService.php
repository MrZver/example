<?php

namespace Boodmo\Sales\Service;

use Boodmo\Email\Service\EmailManager;
use Boodmo\Sales\Entity\Message;
use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Entity\OrderPaymentApplied;
use Boodmo\Sales\Entity\Trigger;
use Boodmo\Sales\Model\Event\NotifyEvent;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Plugin\Transactional\OrderConfirmationEmail;
use Boodmo\Shipping\Service\ShippingService;
use SlmMail\Service\MandrillService;
use Boodmo\Email\Service\Template\TemplateServiceInterface;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\Log\LoggerInterface;
use Zend\Log\Logger;

class NotificationService
{
    use EventManagerAwareTrait;
    /**
     * @var MandrillService
     */
    private $mandrillService;

    /**
     * @var TemplateServiceInterface
     */
    private $templateService;

    /**
     * @var EmailManager
     */
    private $emailManager;

    /**
     * @var ShippingService
     */
    private $shippingService;

    private $config;

    private $_em;

    private $formatPrice;

    /* @var Logger|LoggerInterface */
    private $logger;

    /**
     * NotificationService constructor.
     *
     * @param MandrillService $statusRepository
     */
    public function __construct(
        MandrillService $mandrillService,
        TemplateServiceInterface $templateService,
        EmailManager $emailManager,
        ShippingService $shippingService,
        $config,
        $doctrineManager,
        $formatPrice,
        LoggerInterface $logger
    ) {
        $this->mandrillService = $mandrillService;
        $this->templateService = $templateService;
        $this->emailManager = $emailManager;
        $this->shippingService = $shippingService;
        $this->config = $config;
        $this->_em = $doctrineManager;
        $this->formatPrice = $formatPrice;
        $this->logger = $logger;
    }

    protected function attachDefaultListeners()
    {
        $this->events->attach(
            '*->' . StatusEnum::PROCESSING . '[' . OrderBundle::class . ']',
            \Closure::fromCallable([$this, 'newOrderConfirmation']),
            1
        );
        $this->events->attach(
            '*->' . StatusEnum::DISPATCHED . '[' . OrderPackage::class . ']',
            \Closure::fromCallable([$this, 'dispatchedPackage']),
            1
        );
        $this->events->attach(
            '*->' . StatusEnum::READY_FOR_SHIPPING . '[' . OrderPackage::class . ']',
            \Closure::fromCallable([$this, 'readyForShippingPackage']),
            1
        );
        $this->events->attach(
            '*->' . 'PAID' . '[' . OrderBill::class . ']',
            \Closure::fromCallable([$this, 'paid']),
            1
        );
    }

    private function newOrderConfirmation(NotifyEvent $event): void
    {
        /* @var OrderBundle $orderBundle*/
        $orderBundle = $event->getTarget();
        $this->emailManager->send(
            ($this->emailManager->getTransactional(OrderConfirmationEmail::TEMPLATE_ID))(['order' => $orderBundle])
        );

        $this->sendSmsNotification(
            "Customer: Order Confirmation (SMS)",
            [
                ['name' => 'OrderId', 'content' => $orderBundle->getNumber()]
            ],
            $orderBundle->getCustomerProfile()->getPhone()
        );
    }

    private function dispatchedPackage(NotifyEvent $event): void
    {
        $package = $event->getTarget();
        $trigger = new Trigger();
        $trigger->setTemplate('Customer: OrderPackage Dispatched (SMS)');
        $this->resolveSmsTriggers([$trigger], $package);
    }

    private function paid(NotifyEvent $event): void
    {
        $package = $event->getTarget();
        $trigger = new Trigger();
        $trigger->setTemplate('Customer: Payment received');
        $this->resolveEmailTriggers([$trigger], $package);
        $trigger = new Trigger();
        $trigger->setTemplate('Customer: Payment received (SMS)');
        $this->resolveSmsTriggers([$trigger], $package);
    }

    private function readyForShippingPackage(NotifyEvent $event): void
    {
        $package = $event->getTarget();
        $trigger = new Trigger();
        $trigger->setTemplate('Customer:Order ReadyForShipping');
        $this->resolveEmailTriggers([$trigger], $package);
        $trigger = new Trigger();
        $trigger->setTemplate('Customer: Ready for shipping (SMS)');
        $this->resolveSmsTriggers([$trigger], $package);
    }

    public function sendEmailNotification($templateId, $vars, $package)
    {
        try {
            $message = $this->templateService
                ->setTemplateId($templateId)
                ->setVars($vars)
                ->getMessage();
            $message->addTo($vars['order']['email']);
            $this->emailManager->send($message);
            $message = new Message();
            $message->setType('Email');
            $message->setTo($vars['order']['email']);
            $message->setSubject($templateId);
            $message->setContent($templateId);
            $message->setPackage($package);

            $this->_em->persist($message);
            $this->_em->flush();
        } catch (\Throwable $exception) {
            $extras = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
            $this->logger->log(Logger::ERR, $exception->getMessage().' ['.print_r($vars, 1).']', $extras);
        }
    }

    public function sendSmsNotification($templateId, $vars, $phone, $package = null)
    {
        try {
            $templateBody = $this->mandrillService->renderTemplate($templateId, [], $vars);
            $smsGate = $this->config['sms-gate'];
            $connectionParams = $smsGate['params'];
            $url = $smsGate['host'].$smsGate['route'].
                'uname='.$connectionParams['uname'].
                '&pass='.$connectionParams['pass'].
                '&send='.$connectionParams['from'].
                '&dest='.$phone.'&msg='.rawurlencode($templateBody['html']);
            file_get_contents($url);

            if ($package) {
                $message = new Message();
                $message->setType('SMS');
                $message->setTo($phone);
                $message->setSubject($templateBody['html']);
                $message->setContent($templateBody['html']);
                $message->setPackage($package);

                $this->_em->persist($message);
                $this->_em->flush();
            }
        } catch (\Throwable $exception) {
            $extras = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
            $this->logger->log(Logger::ERR, $exception->getMessage().' ['.print_r($vars, 1).']', $extras);
        }
    }

    public function resolveEmailTriggers($triggers, $orderItem, $payment = false)
    {
        if (is_array($triggers) && !empty($triggers)) {
            if ($orderItem instanceof OrderItem) {
                $package = $orderItem->getPackage();
                $orderBundle = $orderItem->getPackage()->getBundle();
            } elseif ($orderItem instanceof OrderBill) {
                /**
                 * @var $pa OrderPaymentApplied
                 */
                $pa = $orderItem->getPaymentsApplied()->first();
                if (!$pa) {
                    return;
                }

                $payment = $pa->getPayment();
                $orderBundle = $orderItem->getBundle();
                $package = $orderBundle->getPackages()->first() ?? null;
            } else {
                $package = $orderItem;
                $orderBundle = $orderItem->getBundle();
            }
            $transEmail = $this->emailManager->getTransactional(OrderConfirmationEmail::TEMPLATE_ID);
            $transEmail->setOrder($orderBundle);
            $mailVariables = $transEmail->getVars()['order'];
            $packageEvent = [];
            foreach ($mailVariables['packages'] as $packageVar) {
                if ($packageVar['id'] === $package->getNumber()) {
                    $packageEvent = $packageVar;
                }
            }
            if ($payment) {
                $mailVariables['payment'] = ['amount' => ($this->formatPrice)($payment->getTotal())];
            }
            foreach ($triggers as $trigger) {
                $this->sendEmailNotification(
                    $trigger->getTemplate(),
                    ['order' => $mailVariables, 'package' => $packageEvent],
                    $package
                );
            }
        }
    }

    public function resolveSmsTriggers($triggers, $orderItem)
    {
        if (is_array($triggers) && !empty($triggers)) {
            if ($orderItem instanceof OrderItem) {
                $package = $orderItem->getPackage();
                $orderBundle = $package->getBundle();
            } elseif ($orderItem instanceof OrderBill) {
                $orderBundle = $orderItem->getBundle();
                $package = $orderBundle->getPackages()[0] ?? null;
            } else {
                $package = $orderItem;
                $orderBundle = $orderItem->getBundle();
            }
            $transEmail = $this->emailManager->getTransactional(OrderConfirmationEmail::TEMPLATE_ID);
            $transEmail->setOrder($orderBundle);
            $mailVariables = $transEmail->getVars()['order'];
            $trackingNumber = null;
            $shippingMethod = null;
            if ($shippingBox = $package->getShippingBox()) {
                $shippingMethod = $shippingBox->getMethod();
                $trackingNumber = $shippingBox->getTrackNumber();
            }
            $carrier = $shippingMethod ? $this->shippingService->getCarrierByCode($shippingMethod) : null;
            $carrierName = !is_null($carrier) ? $carrier->getCarrierName() : '';
            $vars = [
                'order' => ['name' => 'orderId', 'content' => $mailVariables['id']],
                ['name' => 'orderName', 'content' => $mailVariables['name']],
                ['name' => 'trackingNumber', 'content' => $trackingNumber],
                ['name' => 'carrier', 'content' => $carrierName],
                ['name' => 'packageId', 'content' => $package->getFullNumber()],
            ];
            foreach ($triggers as $trigger) {
                $this->sendSmsNotification($trigger->getTemplate(), $vars, $mailVariables['telephone'], $package);
            }
        }
    }

    public function sendCreditMemoNotification($templateId, $vars)
    {
        $message = $this->templateService
            ->setTemplateId($templateId)
            ->setVars($vars)
            ->getMessage();
        $message->addTo($vars['order']['email']);
        $this->emailManager->send($message);
    }
}
